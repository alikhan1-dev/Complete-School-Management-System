@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="row">
    <div class="col-md-4">
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">Edit Class</h3></div>
            <form method="post" action="{{ route('academics.classes.update', $classRow->id) }}">
                @csrf
                @foreach($selectedSectionIds as $sectionId)
                    <input type="hidden" name="prev_sections[]" value="{{ $sectionId }}">
                @endforeach
                <div class="box-body">
                    <div class="form-group">
                        <label>Class</label> <small class="req">*</small>
                        <input type="text" name="class" class="form-control"
                               value="{{ old('class', $classRow->class) }}" autofocus required>
                    </div>
                    <div class="form-group">
                        <label>Sections</label> <small class="req">*</small>
                        @foreach($sections as $section)
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="sections[]" value="{{ $section->id }}"
                                        {{ in_array($section->id, old('sections', $selectedSectionIds)) ? 'checked' : '' }}>
                                    {{ $section->section }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="box-footer">
                    <a href="{{ route('academics.classes.index') }}" class="btn btn-default">Cancel</a>
                    <button type="submit" class="btn btn-info pull-right">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
