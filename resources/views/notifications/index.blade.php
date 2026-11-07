<x-layouts.app title="Notifications">
    <x-page-header title="Notifications">
        <x-slot:actions><form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button class="btn btn-secondary">Mark all read</button></form></x-slot:actions>
    </x-page-header>
    <div class="card">
        @forelse ($notifications as $n)
            <form method="POST" action="{{ route('notifications.read', $n->id) }}">@csrf
                <button class="flex w-full items-start gap-3 border-b border-slate-100 px-5 py-3 text-left last:border-0 hover:bg-slate-50">
                    <span @class(['mt-1.5 size-2 shrink-0 rounded-full', 'bg-brand-500' => ! $n->read_at, 'bg-transparent' => $n->read_at])></span>
                    <span class="min-w-0"><span @class(['block text-[13px]', 'font-semibold' => ! $n->read_at])>{{ $n->data['title'] ?? 'Notification' }}</span><span class="block text-xs text-slate-600">{{ $n->data['message'] ?? '' }}</span><span class="block text-[11px] text-slate-400">{{ fmt_datetime($n->created_at) }}</span></span>
                </button>
            </form>
        @empty
            <x-empty icon="bell" title="No notifications" />
        @endforelse
        {{ $notifications->links() }}
    </div>
</x-layouts.app>
