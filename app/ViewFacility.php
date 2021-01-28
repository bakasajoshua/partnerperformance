<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ViewFacility extends Model
{
	protected $table = 'view_facilitys';


    public function scopeEligible($query, $offset=0)
    {
        return $query->whereNotNull('DHIScode')
        		->where('DHIScode', '!=', '0')
        		->where('invalid_dhis', 0)
        		->limit(50)->offset($offset);
    }


	public function facility()
	{
		return $this->belongsTo('App\Facility', 'id');
	}

	public function supportedFacility()
	{
		return $this->hasMany('App\SupportedFacility', 'facility_id');
	}
    public function changePartner($partner_id, $start_of_support)
    {
    	$newSupportedFacility = SupportedFacility::create([
    		'facility_id' => $this->id,
    		'partner_id' => $partner_id,
    		'start_of_support' => $start_of_support,
    	]);

    	$prevSupportedFacility = $this->supportedFacility()->whereNull('end_of_support')->where('start_of_support', '!=', $start_of_support)->first();
    	if(!$prevSupportedFacility) return;
        $prevSupportedFacility->end_of_support = Carbon::create($newSupportedFacility->start_of_support)->subDay()->toDateString();
    	$prevSupportedFacility->save();
    }
}
