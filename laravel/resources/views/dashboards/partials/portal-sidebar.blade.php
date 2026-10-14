<aside class="portal-sidebar" id="portal-sidebar" aria-label="Navigasi dashboard">
    <a class="portal-sidebar-brand" href="{{ url()->current() }}">
        <span class="brand-mark"><span></span><span></span><span></span><span></span></span>
        <span>SIMANIS<small>{{ $sidebarRole }}</small></span>
    </a>
    <div class="portal-sidebar-caption">MENU UTAMA</div>
    <nav class="portal-sidebar-nav">
        @foreach ($sidebarGroups as $group)
            <div class="portal-sidebar-group">
                <span class="portal-sidebar-group-title">{{ $group['title'] }}</span>
                @foreach ($group['items'] as $item)
                    <a class="portal-nav-link{{ $loop->parent->first && $loop->first ? ' is-active' : '' }}" href="#{{ $item['id'] }}" data-portal-nav-link @if (isset($item['related'])) data-related="{{ implode(',', $item['related']) }}" @endif>
                        <span class="portal-nav-icon" aria-hidden="true">{{ $item['icon'] }}</span>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        @endforeach
    </nav>
    <div class="portal-sidebar-bottom">
        <span class="portal-sidebar-user">{{ auth()->user()->name }}</span>
        <span class="portal-sidebar-account">{{ $sidebarRole }}</span>
    </div>
</aside>
<button class="portal-sidebar-scrim" type="button" aria-label="Tutup navigasi" data-sidebar-scrim></button>
