@php
    $selected_content = $selected_content ?? [];
    $isSelected = fn ($id) => in_array((int) $id, $selected_content, true);
@endphp

<div class="row flex-column-sm grid_div {{ $grid_view ? 'displayblock' : 'displaynone' }}">
    <div class="col-lg-12 col-md-12 col-sm-12 order-2 order-lg-1">
        <div class="row">
            @forelse($all_contents as $content_value)
                <div class="col-lg-4 col-sm-12 col-md-6 top_list_div"
                     data-record-id="{{ $content_value->id }}"
                     data-real_name="{{ $content_value->real_name }}"
                     data-short_name="{{ $uploads->fileview((string) $content_value->img_name) }}"
                     data-type-id="{{ $content_value->content_type_id }}"
                     data-file-type="{{ $content_value->file_type }}"
                     data-name="{{ $content_value->file_type == 'video' ? $content_value->vid_url : $content_value->img_name }}"
                     data-path="{{ $content_value->dir_path }}">
                    <article class="card card-product-list">
                        <div class="">
                            <aside class="img-wrap-fix-mobile div_image">
                                <a href="javascript:void(0);" class="img-wrap image_content">
                                    <img class="p-2" src="{{ $uploads->fileIconUrl($content_value) }}">
                                </a>
                            </aside>
                            <div class="img-wrap-fix-right content_list">
                                <div class="content-card-body relative flex-column">
                                    <div class="radio-title">
                                        <input type="checkbox" name="share_checkbox[]"
                                               data-real_name="{{ $content_value->real_name }}"
                                               value="{{ $content_value->id }}"
                                               data-name="{{ $content_value->img_name }} "
                                               class="float-end share_checkbox relative z-index-1"
                                               @checked($isSelected($content_value->id))>
                                        <aside class="div_image">
                                            <a href="javascript:void(0);" class="image_content">
                                                {{ $content_value->file_type == 'video' ? $content_value->vid_title : $content_value->real_name }}
                                            </a>
                                        </aside>
                                    </div>
                                    <div class="price-wrap me-3">
                                        {{ $uploads->staffFullName($content_value->staff_name, $content_value->surname, $content_value->employee_id) }}
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between content-footer-bottom">
                                    <div>
                                        <span class="price h6">{{ $uploads->formatCreatedAt($content_value->created_at) }}</span>
                                    </div>
                                    <div class="inline-anchor white-space-nowrap">
                                        <a href="{{ url('admin/content/download_content/'.$content_value->id) }}" class="text-default download_file pr-05" data-toggle="tooltip" title="{{ __('system.download') }}"><i class="fa fa-download"></i></a>
                                        @if(!empty($canDelete))
                                            <a href="#" class="text-danger delete_file" data-record-id="{{ $content_value->id }}" data-name="{{ $content_value->file_type == 'video' ? $content_value->vid_title : $content_value->real_name }}" data-toggle="modal" data-target="#single-delete"><span class="display-inline-block" data-toggle="tooltip" title="{{ __('system.delete') }}"><i class="fa fa-trash-o"></i></span></a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            @empty
                <div class="col-12 col-sm-6 col-md-12">
                    <div class="alert alert-info">{{ __('system.no_record_found') }}</div>
                </div>
            @endforelse
        </div>
    </div>
</div>

<div class="row list_div {{ ! $grid_view ? 'displayblock' : 'displaynone' }}">
    @if($all_contents->isNotEmpty())
        <div class="col-lg-12">
            <div class="table-responsive">
                <table class="table table-bordered table_contents">
                    <thead>
                        <tr>
                            <th width="30">#</th>
                            <th width="30">{{ __('system.document') }}</th>
                            <th width="30">{{ __('system.content_type') }}</th>
                            <th width="30">{{ __('system.size') }}</th>
                            <th width="30">{{ __('system.upload_by') }}</th>
                            <th width="30" class="pull-right">{{ __('system.created_on') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($all_contents as $content_value)
                            <tr data-record-id="{{ $content_value->id }}"
                                data-real_name="{{ $content_value->real_name }}"
                                data-short_name="{{ $uploads->fileview((string) $content_value->img_name) }}"
                                data-type-id="{{ $content_value->content_type_id }}"
                                data-file-type="{{ $content_value->file_type }}"
                                data-name="{{ $content_value->file_type == 'video' ? $content_value->vid_url : $content_value->img_name }}"
                                data-path="{{ $content_value->dir_path }}">
                                <td>
                                    <input type="checkbox" name="share_checkbox[]"
                                           data-real_name="{{ $content_value->real_name }}"
                                           value="{{ $content_value->id }}"
                                           data-name="{{ $content_value->img_name }} "
                                           class="share_checkbox_list"
                                           @checked($isSelected($content_value->id))>
                                    <input type="hidden" name="image_display" value="{{ $uploads->fileIconUrl($content_value) }}">
                                </td>
                                <td>
                                    @if($content_value->file_type == 'video')
                                        <a href="{{ $content_value->vid_url }}" target="_blank">{{ $content_value->vid_title }}</a>
                                    @else
                                        <a href="javascript:void(0);">{{ $content_value->real_name }}</a>
                                    @endif
                                </td>
                                <td>{{ $content_value->content_type }}</td>
                                <td>{{ $content_value->file_type == 'video' ? __('system.n_a') : $uploads->formatFileSize($content_value->file_size) }}</td>
                                <td>{{ $uploads->staffFullName($content_value->staff_name, $content_value->surname, $content_value->employee_id) }}</td>
                                <td class="pull-right">{{ $uploads->formatCreatedAt($content_value->created_at) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="col-12 col-sm-6 col-md-12">
            <div class="alert alert-info">{{ __('system.no_record_found') }}</div>
        </div>
    @endif
</div>
