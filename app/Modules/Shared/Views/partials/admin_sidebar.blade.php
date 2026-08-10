@php
    use App\Modules\Roles\Models\PermissionGroup;
    use App\Modules\Roles\Services\PermissionService;
    use App\Modules\Shared\Support\MenuPermissionParser;
    use Illuminate\Support\Facades\DB;

    $permissions = app(PermissionService::class);
    $parser = app(MenuPermissionParser::class);
    $menus = DB::table('sidebar_menus')->orderBy('level')->get();
@endphp
<ul class="sidebar-menu" data-widget="tree">
    <li class="header">MAIN NAVIGATION</li>
    <li><a href="{{ route('admin.dashboard') }}"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
    <li><a href="{{ route('roles.index') }}"><i class="fa fa-users"></i> <span>Roles</span></a></li>
    <li><a href="{{ route('roles.student_permissions') }}"><i class="fa fa-lock"></i> <span>Student Permissions</span></a></li>
    <li><a href="{{ route('staff.index') }}"><i class="fa fa-user"></i> <span>Staff</span></a></li>
    @foreach($menus as $menu)
        @php
            $allowed = $parser->evaluate((string) ($menu->access_permissions ?? ''), function ($cat, $ability) use ($permissions) {
                return $permissions->hasPrivilege($cat, $ability);
            });
            $moduleOk = true;
            if (!empty($menu->permission_group_id)) {
                $moduleOk = PermissionGroup::query()->where('id', $menu->permission_group_id)->where('is_active', 1)->exists();
            }
        @endphp
        @if($allowed && $moduleOk)
            <li><a href="#"><i class="fa fa-circle-o"></i> <span>{{ $menu->menu ?? $menu->lang_key ?? 'Menu' }}</span></a></li>
        @endif
    @endforeach
</ul>
