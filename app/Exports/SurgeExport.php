<?php

namespace App\Exports;

use DB;

class SurgeExport extends BaseExport
{
	protected $week_id;
	protected $modalities;
	protected $gender_id;
	protected $ages;

    function __construct($request)
    {
    	parent::__construct();
		$this->week_id = $request->input('week_id');
		$this->modalities = $request->input('modalities');
		$this->gender_id = $request->input('gender_id');
		$this->ages = $request->input('ages');

  //   function __construct()
  //   {
		// $this->week_id = 35;
		// $this->modalities = [1,2];
		// $this->gender_id = null;
		// $this->ages = null;
		// $this->partner = \App\Partner::find(55);


		$week = \App\Week::findOrFail($this->week_id);
		$this->fileName = $this->partner->download_name . '_surge_data_for_' . $week->start_date . '_to_' . $week->end_date . '.xlsx';


    	$modalities = $this->modalities;
    	$gender_id = $this->gender_id;
    	$ages = $this->ages;
    	$partner = $this->partner;
    	$week_id = $this->week_id;

		$columns = \App\SurgeColumn::when(true, function($query) use ($modalities){
				if(is_array($modalities)) return $query->whereIn('modality_id', $modalities);
				return $query->where('modality_id', $modalities);
			})->when($gender_id, function($query) use ($gender_id){
				return $query->where('gender_id', $gender_id);
			})->when($ages, function($query) use ($ages){
				if(is_array($ages)) return $query->whereIn('age_id', $ages);
				return $query->where('age_id', $ages);
			})
			->orderBy('modality_id', 'asc')
			->orderBy('gender_id', 'asc')
			->orderBy('age_id', 'asc')
			->orderBy('id', 'asc')
			->get();

		$sql = "countyname as County, Subcounty, facilitycode AS `MFL Code`, name AS `Facility`, financial_year AS `Financial Year`, week_number as `Week Number`";

		foreach ($columns as $column) {
			$sql .= ", `{$column->column_name}` AS `{$column->alias_name}`";
		}
		$this->sql = $sql;
    }

    public function headings() : array
    {
    	$partner = $this->partner;
    	$week_id = $this->week_id;

		$row = DB::table('d_surge')
			->join('view_facilitys', 'view_facilitys.id', '=', 'd_surge.facility')
			->join('weeks', 'weeks.id', '=', 'd_surge.week_id')
			->selectRaw($this->sql)
			->where('week_id', $week_id)
			->where('partner', $partner->id)
			->first();

		return collect($row)->keys()->all();
    }


    public function query()
    {
    	$modalities = $this->modalities;
    	$gender_id = $this->gender_id;
    	$ages = $this->ages;
    	$partner = $this->partner;
    	$week_id = $this->week_id;

		$columns = \App\SurgeColumn::when(true, function($query) use ($modalities){
				if(is_array($modalities)) return $query->whereIn('modality_id', $modalities);
				return $query->where('modality_id', $modalities);
			})->when($gender_id, function($query) use ($gender_id){
				return $query->where('gender_id', $gender_id);
			})->when($ages, function($query) use ($ages){
				if(is_array($ages)) return $query->whereIn('age_id', $ages);
				return $query->where('age_id', $ages);
			})
			->orderBy('modality_id', 'asc')
			->orderBy('gender_id', 'asc')
			->orderBy('age_id', 'asc')
			->orderBy('id', 'asc')
			->get();

		$sql = "countyname as County, Subcounty, facilitycode AS `MFL Code`, name AS `Facility`, financial_year AS `Financial Year`, week_number as `Week Number`";

		foreach ($columns as $column) {
			$sql .= ", `{$column->column_name}` AS `{$column->alias_name}`";
		}

		$facilities = \App\Facility::select('id')->where(['is_surge' => 1, 'partner' => $partner->id])->get()->pluck('id')->toArray();
		
		return DB::table('d_surge')
			->join('view_facilitys', 'view_facilitys.id', '=', 'd_surge.facility')
			->join('weeks', 'weeks.id', '=', 'd_surge.week_id')
			->selectRaw($this->sql)
			->where('week_id', $week_id)
			->where('partner', $partner->id)
			->when($facilities, function($query) use ($facilities){
				return $query->whereIn('view_facilitys.id', $facilities);
			})
			->orderBy('name', 'asc');
    }
}
