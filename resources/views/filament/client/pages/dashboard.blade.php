<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Welcome Section -->
        <x-filament::section>
            <x-slot name="heading">
                Welcome back, {{ auth()->user()->name }}!
            </x-slot>
            Here's an overview of your leads for {{ auth()->user()->client->name ?? 'your business' }}.
        </x-filament::section>

        <!-- Stats Widgets -->
        <x-filament-widgets::widgets :widgets="[\App\Filament\Client\Widgets\LeadStatsWidget::class]" />

        <!-- Quick Actions -->
        <x-filament::section>
            <x-slot name="heading">Quick Actions</x-slot>

            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-4">
                <x-filament::card>
                    <a href="{{ route('filament.client.resources.leads.index') }}"
                        class="block p-4 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <x-heroicon-o-user-group class="h-8 w-8 text-primary-600" />
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">View All Leads</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Manage your customer inquiries</p>
                            </div>
                        </div>
                    </a>
                </x-filament::card>

                <x-filament::card>
                    <a href="{{ route('filament.client.resources.leads.index', ['tableFilters' => ['status' => ['values' => ['new']]]]) }}"
                        class="block p-4 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <x-heroicon-o-bell class="h-8 w-8 text-success-600" />
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">New Leads</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Leads awaiting your response</p>
                            </div>
                        </div>
                    </a>
                </x-filament::card>

                <x-filament::card>
                    <a href="{{ route('filament.client.resources.leads.index', ['tableFilters' => ['needs_attention' => ['isActive' => true]]]) }}"
                        class="block p-4 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <x-heroicon-o-exclamation-triangle class="h-8 w-8 text-warning-600" />
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Needs Attention</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Leads requiring follow-up</p>
                            </div>
                        </div>
                    </a>
                </x-filament::card>

                <x-filament::card>
                    <a href="{{ route('filament.client.resources.leads.index', ['tableFilters' => ['status' => ['values' => ['lost']]]]) }}"
                        class="block p-4 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <x-heroicon-o-arrow-path class="h-8 w-8 text-danger-600" />
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Lost Leads</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Recontact potential customers</p>
                            </div>
                        </div>
                    </a>
                </x-filament::card>
            </div>
        </x-filament::section>

        <!-- Recent Leads Table -->
        <x-filament-widgets::widgets :widgets="[\App\Filament\Client\Widgets\RecentLeadsWidget::class]" />
    </div>
</x-filament-panels::page>
