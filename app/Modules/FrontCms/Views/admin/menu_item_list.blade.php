<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Menu Item List</h3>
        <div class="box-tools pull-right">
            @foreach($listMenus as $navMenu)
                <a href="{{ url('admin/front/menus/additem/'.$navMenu['slug']) }}" class="btn btn-xs {{ ($top_menu ?? '') === $navMenu['slug'] ? 'btn-primary' : 'btn-default' }}">{{ $navMenu['menu'] }}</a>
            @endforeach
        </div>
    </div>
    <div class="box-body">
        <ul>
            @forelse($listdropdown_Menus as $item)
                <li>
                    {{ $item['menu'] }}
                    @if(!empty($canEdit))
                        <a href="{{ url('admin/front/menus/edititem/'.$item['slug'].'/'.$top_menu) }}" class="btn btn-primary btn-xs">Edit</a>
                    @endif
                    @if(!empty($canDelete))
                        <form method="post" action="{{ url('admin/front/menus/deleteMenuItem') }}" style="display:inline;">
                            @csrf
                            <input type="hidden" name="id" value="{{ $item['id'] }}">
                            <button type="submit" class="btn btn-primary btn-xs" onclick="return confirm('Are you sure?');">Delete</button>
                        </form>
                    @endif
                    @if(!empty($item['submenus']))
                        <ul>
                            @foreach($item['submenus'] as $sub)
                                <li>
                                    {{ $sub['menu'] }}
                                    @if(!empty($canEdit))
                                        <a href="{{ url('admin/front/menus/edititem/'.$sub['slug'].'/'.$top_menu) }}" class="btn btn-primary btn-xs">Edit</a>
                                    @endif
                                    @if(!empty($canDelete))
                                        <form method="post" action="{{ url('admin/front/menus/deleteMenuItem') }}" style="display:inline;">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $sub['id'] }}">
                                            <button type="submit" class="btn btn-primary btn-xs" onclick="return confirm('Are you sure?');">Delete</button>
                                        </form>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @empty
                <li>No record found.</li>
            @endforelse
        </ul>
        <p class="help-block">Reorder posts JSON to <code>admin/front/menus/updateMenu</code> as CI nestedSortable does.</p>
    </div>
</div>
