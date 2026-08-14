<div class="row">
    <div class="form-group col-sm-6">
        <label>Search By File Name</label>
        <input type="text" class="form-control search_text" placeholder="Enter Keyword">
    </div>
    <div class="form-group col-sm-6">
        <label>Search By File Type</label>
        <select class="form-control file_type">
            <option value="">Select</option>
            @foreach($mediaTypes as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>
<div class="row" id="media_div"></div>
<div align="right" id="pagination_link"></div>
