			<div class="modal-header">
			  
 <button type="button" class="close" data-dismiss="modal" >&times;</button>
	 <h4 class="modal-title title modal_title">{{ $location->name ?? '' }}</h4>
</div> 
			<div class="box-body">
		  
<div id="sample" style="height:400px;width:100%"></div>
<p class="help-block" style="margin-top:10px;">
    {{ __('system.latitude') }}: {{ $location->latitude ?? '' }}
    &nbsp;|&nbsp;
    {{ __('system.longitude') }}: {{ $location->longitude ?? '' }}
    &nbsp;|&nbsp;
    <a href="https://www.google.com/maps?q={{ urlencode(($location->latitude ?? '').','.($location->longitude ?? '')) }}"
       target="_blank" rel="noopener">{{ __('system.map') }}</a>
</p>
</div>
