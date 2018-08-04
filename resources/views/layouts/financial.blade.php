
<div class="row" id="filter">
	<div class="col-md-4">
		<select class="btn filters" id="filter_county">
			<option disabled='true' selected='true'>Select County</option>
			<option value='null' selected='true'>All Counties</option>

			@foreach($counties as $county)
				<option value="{{ $county->id }}"> {{ $county->name }} </option>
			@endforeach
		</select>		
	</div>	

	<div class="col-md-4">
		<select class="btn filters" id="filter_subcounty">
			<option disabled='true' selected='true'>Select Subcounty</option>
			<option value='null' selected='true'>All Subcounties</option>

			@foreach($subcounties as $subcounty)
				<option value="{{ $subcounty->id }}"> {{ $subcounty->name }} </option>
			@endforeach
		</select>		
	</div>	

	<div class="col-md-4">
		<select class="btn filters" id="filter_partner">
			<option disabled='true' selected='true'>Select Partner</option>
			<option value='null' selected='true'>All Partners</option>

			@foreach($partners as $partner)
				<option value="{{ $partner->id }}"> {{ $partner->name }} </option>
			@endforeach
		</select>		
	</div>		

	<div class="col-md-4">
		<select class="btn" id="filter_facility">
			<option disabled='true' selected='true'>Select Facility</option>
			<option value='null' selected='true'>All Facilities</option>

		</select>		
	</div>		

	<div class="col-md-4">
		<select class="btn filters" id="filter_groupby">
			<option disabled='true' selected='true'>Group By:</option>

			@foreach($divisions as $division)
				<option value="{{ $division->id }}"> {{ $division->name }} </option>
			@endforeach
		</select>		
	</div>	

	<div class="col-md-4">
		<center>
			<a href="javascript:void(0)" onclick="date_filter('financial_year', 2018, '{{ $date_url }}')" class="alert-link"> FY 2018 </a>|
			<a href="javascript:void(0)" onclick="date_filter('financial_year', 2019, '{{ $date_url }}')" class="alert-link"> FY 2019 </a>|
		</center>		
	</div>
	
</div>