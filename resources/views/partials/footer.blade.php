<!-- Main Footer -->
<footer class="main-footer">
    <!-- To the right -->
    <div class="pull-right hidden-xs">
        <strong>Version</strong>&nbsp;&nbsp; {!! config('admin.version') !!}
    </div>
    <!-- Default to the left -->
    <strong>
        @if (!empty(config('admin.powerd_by_view')))
            {!! config('admin.powerd_by_view') !!}
        @else
            Powered by <a href="https://github.com/z-song/laravel-admin" target="_blank">laravel-admin</a>
        @endif
    </strong>
</footer>