<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class LeadController extends Controller
{
    /**
     * Get leads for reporting with filtering options
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Admin users can access all clients' data
        // Regular users can only access their client's data
        if (!$user->isAdmin() && !$user->client_id) {
            return response()->json(['error' => 'User not associated with a client'], 403);
        }

        // Build base query
        $query = Lead::with('client:id,name,company');
        
        // For non-admin users, scope to their client
        if (!$user->isAdmin()) {
            $query->where('client_id', $user->client_id);
        }

        // Apply client filter (for admin users to filter specific clients)
        if ($request->has('client_id') && $user->isAdmin()) {
            $query->where('client_id', $request->client_id);
        }

        // Apply date filters
        if ($request->has('start_date')) {
            $query->where('created_at', '>=', Carbon::parse($request->start_date)->startOfDay());
        }

        if ($request->has('end_date')) {
            $query->where('created_at', '<=', Carbon::parse($request->end_date)->endOfDay());
        }

        // Apply campaign filter
        if ($request->has('campaign')) {
            if ($request->campaign === 'null' || $request->campaign === '') {
                $query->whereNull('campaign');
            } else {
                $query->where('campaign', $request->campaign);
            }
        }

        // Apply source filter
        if ($request->has('source')) {
            $query->where('source', $request->source);
        }

        // Apply status/disposition filter
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Order by creation date (newest first)
        $query->orderBy('created_at', 'desc');

        // Paginate results
        $perPage = min($request->get('per_page', 50), 100); // Max 100 per page
        $leads = $query->paginate($perPage);

        // Transform the data to include all the fields you need
        $transformedLeads = $leads->through(function ($lead) {
            return [
                'id' => $lead->id,
                'name' => $lead->name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'message' => $lead->message,
                'notes' => $lead->notes,
                'from_email' => $lead->from_email,
                'email_subject' => $lead->email_subject,
                'status' => $lead->status, // disposition
                'source' => $lead->source, // lead source
                'campaign' => $lead->campaign,
                'client_id' => $lead->client_id,
                'client' => [
                    'id' => $lead->client->id,
                    'name' => $lead->client->name,
                    'company' => $lead->client->company ?? $lead->client->name,
                ],
                'created_at' => $lead->created_at->toISOString(),
                'updated_at' => $lead->updated_at->toISOString(),
                'email_received_at' => $lead->email_received_at ? Carbon::parse($lead->email_received_at)->toISOString() : null,
            ];
        });

        return response()->json([
            'data' => $transformedLeads->items(),
            'meta' => [
                'current_page' => $leads->currentPage(),
                'per_page' => $leads->perPage(),
                'total' => $leads->total(),
                'last_page' => $leads->lastPage(),
                'from' => $leads->firstItem(),
                'to' => $leads->lastItem(),
            ],
            'filters_applied' => [
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'client_id' => $request->client_id,
                'campaign' => $request->campaign,
                'source' => $request->source,
                'status' => $request->status,
            ]
        ]);
    }

    /**
     * Get aggregated statistics for leads
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Admin users can access all clients' data
        // Regular users can only access their client's data
        if (!$user->isAdmin() && !$user->client_id) {
            return response()->json(['error' => 'User not associated with a client'], 403);
        }

        // Get date range
        $startDate = $request->has('start_date') 
            ? Carbon::parse($request->start_date)->startOfDay()
            : Carbon::now()->subDays(30)->startOfDay();
            
        $endDate = $request->has('end_date')
            ? Carbon::parse($request->end_date)->endOfDay()
            : Carbon::now()->endOfDay();

        // Build base query
        $query = Lead::with('client:id,name,company')
            ->whereBetween('created_at', [$startDate, $endDate]);
        
        // For non-admin users, scope to their client
        if (!$user->isAdmin()) {
            $query->where('client_id', $user->client_id);
        }

        // Apply client filter (for admin users to filter specific clients)
        if ($request->has('client_id') && $user->isAdmin()) {
            $query->where('client_id', $request->client_id);
        }

        // Get basic counts
        $totalLeads = $query->count();
        
        // Group by status/disposition
        $byStatus = $query->clone()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Group by source
        $bySource = $query->clone()
            ->selectRaw('source, COUNT(*) as count')
            ->groupBy('source')
            ->pluck('count', 'source')
            ->toArray();

        // Group by campaign
        $byCampaign = $query->clone()
            ->selectRaw('COALESCE(campaign, "No Campaign") as campaign, COUNT(*) as count')
            ->groupBy('campaign')
            ->pluck('count', 'campaign')
            ->toArray();

        // Group by client (for admin users)
        $byClient = [];
        if ($user->isAdmin() && !$request->has('client_id')) {
            $byClient = Lead::whereBetween('leads.created_at', [$startDate, $endDate])
                ->join('clients', 'leads.client_id', '=', 'clients.id')
                ->selectRaw('clients.name as client_name, clients.id as client_id, COUNT(*) as count')
                ->groupBy('clients.id', 'clients.name')
                ->get()
                ->pluck('count', 'client_name')
                ->toArray();
        }

        // Daily breakdown
        $dailyBreakdown = $query->clone()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        $response = [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'days' => $startDate->diffInDays($endDate) + 1,
            ],
            'totals' => [
                'total_leads' => $totalLeads,
                'avg_per_day' => round($totalLeads / ($startDate->diffInDays($endDate) + 1), 2),
            ],
            'breakdown' => [
                'by_status' => $byStatus,
                'by_source' => $bySource,
                'by_campaign' => $byCampaign,
            ],
            'daily_breakdown' => $dailyBreakdown,
        ];

        // Add client breakdown for admin users
        if ($user->isAdmin() && !$request->has('client_id')) {
            $response['breakdown']['by_client'] = $byClient;
        }

        return response()->json($response);
    }

    /**
     * Get a specific lead
     */
    public function show(Request $request, Lead $lead): JsonResponse
    {
        $user = $request->user();
        
        // Admin users can access any lead, regular users only their client's leads
        if (!$user->isAdmin() && $lead->client_id !== $user->client_id) {
            return response()->json(['error' => 'Lead not found'], 404);
        }

        // Load client relationship
        $lead->load('client:id,name,company');

        return response()->json([
            'data' => [
                'id' => $lead->id,
                'name' => $lead->name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'message' => $lead->message,
                'notes' => $lead->notes,
                'from_email' => $lead->from_email,
                'email_subject' => $lead->email_subject,
                'status' => $lead->status,
                'source' => $lead->source,
                'campaign' => $lead->campaign,
                'client_id' => $lead->client_id,
                'client' => [
                    'id' => $lead->client->id,
                    'name' => $lead->client->name,
                    'company' => $lead->client->company ?? $lead->client->name,
                ],
                'created_at' => $lead->created_at->toISOString(),
                'updated_at' => $lead->updated_at->toISOString(),
                'email_received_at' => $lead->email_received_at ? Carbon::parse($lead->email_received_at)->toISOString() : null,
            ]
        ]);
    }

    /**
     * Get list of clients (admin only)
     */
    public function clients(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->isAdmin()) {
            return response()->json(['error' => 'Admin access required'], 403);
        }

        $clients = Client::select('id', 'name', 'company', 'email')
            ->withCount('leads')
            ->orderBy('name')
            ->get()
            ->map(function ($client) {
                return [
                    'id' => $client->id,
                    'name' => $client->name,
                    'company' => $client->company ?? $client->name,
                    'email' => $client->email,
                    'leads_count' => $client->leads_count,
                ];
            });

        return response()->json([
            'data' => $clients,
            'total_clients' => $clients->count(),
        ]);
    }
}
