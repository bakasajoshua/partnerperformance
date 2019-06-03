<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Excel;
use App\Lookup;
use App\Facility;

use App\Week;
use App\SurgeAge;
use App\SurgeGender;
use App\SurgeModality;
use App\SurgeColumn;
use App\SurgeColumnView;
// use App\Surge;

class SurgeController extends Controller
{
	// pns cascade

	public function testing()
	{
		$tested_columns = SurgeColumnView::where('column_name', 'like', '%tested%')
			->where('hts', 1)
			->when(true, $this->surge_columns_callback())
			->get();

		$positive_columns = SurgeColumnView::where('column_name', 'like', '%positive%')
			->where('hts', 1)
			->when(true, $this->surge_columns_callback())
			->get();

		$sql = $this->get_sum($tested_columns, 'tests') . ', ' . $this->get_sum($positive_columns, 'pos') . ', SUM(testing_target) AS testing_target, SUM(pos_target) AS pos_target ';

		$rows = DB::table('d_surge')
			->join('weeks', 'weeks.id', '=', 'd_surge.week_id')
			->join('view_facilitys', 'view_facilitys.id', '=', 'd_surge.facility')
			->selectRaw($sql)
			->when(true, $this->get_callback('tests'))
			->get();

		// dd($rows);

		$groupby = session('filter_groupby', 1);
		$data['div'] = str_random(15);

		$data['outcomes'][0]['name'] = "Positive Tests";
		$data['outcomes'][1]['name'] = "Negative Tests";
		$data['outcomes'][2]['name'] = "Targeted Tests";
		$data['outcomes'][3]['name'] = "Yield";
		$data['outcomes'][4]['name'] = "Targeted Yield";

		$data['outcomes'][0]['type'] = "column";
		$data['outcomes'][1]['type'] = "column";
		$data['outcomes'][2]['type'] = "spline";
		$data['outcomes'][3]['type'] = "spline";
		$data['outcomes'][4]['type'] = "spline";

		$data['outcomes'][0]['tooltip'] = array("valueSuffix" => ' ');
		$data['outcomes'][1]['tooltip'] = array("valueSuffix" => ' ');
		$data['outcomes'][2]['tooltip'] = array("valueSuffix" => ' ');
		$data['outcomes'][3]['tooltip'] = array("valueSuffix" => ' %');
		$data['outcomes'][4]['tooltip'] = array("valueSuffix" => ' %');

		$data['outcomes'][0]['yAxis'] = 1;
		$data['outcomes'][1]['yAxis'] = 1;
		$data['outcomes'][2]['yAxis'] = 1;

		if($groupby < 10){
			$splines = [2, 3, 4];
			foreach ($splines as $key => $spline) {
				$data['outcomes'][$spline]['lineWidth'] = 0;
				$data['outcomes'][$spline]['marker'] = ['enabled' => true, 'radius' => 4];
				$data['outcomes'][$spline]['states'] = ['hover' => ['lineWidthPlus' => 0]];
			}
		}

		$i = 0;
		foreach ($rows as $key => $row) {
			$data['categories'][$key] = Lookup::get_category($row);
			if($row->tests < $row->pos) $row->tests = $row->pos;
			$data["outcomes"][0]["data"][$key] = (int) $row->pos;	
			$data["outcomes"][1]["data"][$key] = (int) ($row->tests - $row->pos);	
			$data["outcomes"][2]["data"][$key] = (int) $row->testing_target;
			$data["outcomes"][3]["data"][$key] = Lookup::get_percentage($row->pos, $row->tests);
			$data["outcomes"][4]["data"][$key] = Lookup::get_percentage($row->pos_target, $row->testing_target);
		}
		return view('charts.dual_axis', $data);
	}

	public function linkage()
	{
		$positive_columns = SurgeColumnView::where('column_name', 'like', '%positive%')
			->where('hts', 1)
			->when(true, $this->surge_columns_callback(false, false))
			->get();

		$male_new = SurgeColumnView::where('modality', 'tx_new')
			->when(true, $this->surge_columns_callback(false, false))
			->where('gender_id', 1)
			->get();

		$female_new = SurgeColumnView::where('modality', 'tx_new')
			->when(true, $this->surge_columns_callback(false, false))
			->where('gender_id', 2)
			->get();

		$sql = $this->get_sum($positive_columns, 'pos') . ', ' .  $this->get_sum($male_new, 'male_new') . ', ' .  $this->get_sum($female_new, 'female_new');

		$rows = DB::table('d_surge')
			->join('weeks', 'weeks.id', '=', 'd_surge.week_id')
			->join('view_facilitys', 'view_facilitys.id', '=', 'd_surge.facility')
			->selectRaw($sql)
			->when(true, $this->get_callback('pos'))
			->get();

		$groupby = session('filter_groupby', 1);
		$data['div'] = str_random(15);
		$data['yAxis'] = "New On Treatment";
		$data['yAxis2'] = "Linkage to Treatment (%)";

		$data['outcomes'][0]['name'] = "Male New on Treatment";
		$data['outcomes'][1]['name'] = "Female New on Treatment";
		$data['outcomes'][2]['name'] = "Linkage to Treatment";

		$data['outcomes'][0]['type'] = "column";
		$data['outcomes'][1]['type'] = "column";
		$data['outcomes'][2]['type'] = "spline";

		$data['outcomes'][0]['tooltip'] = array("valueSuffix" => ' ');
		$data['outcomes'][1]['tooltip'] = array("valueSuffix" => ' ');
		$data['outcomes'][2]['tooltip'] = array("valueSuffix" => ' %');

		$data['outcomes'][0]['yAxis'] = 1;
		$data['outcomes'][1]['yAxis'] = 1;

		if($groupby < 10){
			$data['outcomes'][2]['lineWidth'] = 0;
			$data['outcomes'][2]['marker'] = ['enabled' => true, 'radius' => 4];
			$data['outcomes'][2]['states'] = ['hover' => ['lineWidthPlus' => 0]];
		}

		foreach ($rows as $key => $row) {
			$data['categories'][$key] = Lookup::get_category($row);
			// if($row->tests < $row->pos) $row->tests = $row->pos;
			$data["outcomes"][0]["data"][$key] = (int) $row->male_new;
			$data["outcomes"][1]["data"][$key] = (int) $row->female_new;
			$data["outcomes"][2]["data"][$key] = Lookup::get_percentage(($row->male_new + $row->female_new), $row->pos);
		}
		return view('charts.dual_axis', $data);
	}


	// Yield by modality
	public function modality_yield()
	{
		$sql = '';

		$groupby = session('filter_groupby', 1);
		$data['div'] = str_random(15);
		$data['yAxis'] = "Yield by Modality (%)";
		$data['suffix'] = '%';
		$data['stacking'] = true;
		$data['extra_tooltip'] = true;


		$modalities = SurgeModality::where('hts', 1)
			->when(session('filter_gender'), function($query){
				if(session('filter_gender') == 1) return $query->where('male', 1);
				if(session('filter_gender') == 2) return $query->where('female', 1);
				if(session('filter_gender') == 3) return $query->where('unknown', 1);
			})
			->get();

		foreach ($modalities as $key => $modality) {
			$tested_columns = SurgeColumnView::where('modality_id', $modality->id)
				->where('column_name', 'like', '%tested%')
				->when(true, $this->surge_columns_callback(false))
				->get();

			$positive_columns = SurgeColumnView::where('modality_id', $modality->id)
				->where('column_name', 'like', '%positive%')
				->when(true, $this->surge_columns_callback(false))
				->get();

			$sql .= $this->get_sum($tested_columns, $modality->modality . '_tested') . ', ' . $this->get_sum($positive_columns, $modality->modality . '_pos') . ', ';

			$data['outcomes'][$key]['name'] = $modality->modality_name;
			$data['outcomes'][$key]['type'] = "column";
		}

		$sql = substr($sql, 0, -2);

		$rows = DB::table('d_surge')
			->join('weeks', 'weeks.id', '=', 'd_surge.week_id')
			->join('view_facilitys', 'view_facilitys.id', '=', 'd_surge.facility')
			->selectRaw($sql)
			->when(true, $this->get_callback())
			->get();


		foreach ($rows as $key => $row){
			$data['categories'][$key] = Lookup::get_category($row);

			foreach ($modalities as $mod_key => $modality) {
				$t = $modality->modality . '_tested';
				$p = $modality->modality . '_pos';
				$data["outcomes"][$mod_key]["data"][$key]['y'] = Lookup::get_percentage($row->$p, $row->$t);
				$data["outcomes"][$mod_key]["data"][$key]['z'] = ' of ' . number_format($row->$t) . ' Tests';
			}
		}
		return view('charts.line_graph', $data);
	}


	// Yield by age
	/*public function age_yield()
	{
		$sql = '';

		$groupby = session('filter_groupby', 1);
		$data['div'] = str_random(15);
		$data['yAxis'] = "Yield by Age (%)";
		$data['suffix'] = '%';

		$ages = SurgeAge::when(session('filter_gender'), function($query){
						if(session('filter_gender') == 3) return $query->where('no_gender', 1);
					})->get();

		foreach ($ages as $key => $age) {
			$tested_columns = SurgeColumnView::where('age_id', $age->id)
				->where('column_name', 'like', '%tested%')
				->when(true, $this->surge_columns_callback(true, true, false))
				->get();

			$positive_columns = SurgeColumnView::where('age_id', $age->id)
				->where('column_name', 'like', '%positive%')
				->when(true, $this->surge_columns_callback(true, true, false))
				->get();

			$sql .= $this->get_sum($tested_columns, $age->age . '_tested') . ', ' . $this->get_sum($positive_columns, $age->age . '_pos') . ', ';

			$data['outcomes'][$key]['name'] = $age->age_name;
			$data['outcomes'][$key]['type'] = "column";
		}

		$sql = substr($sql, 0, -2);

		$rows = DB::table('d_surge')
			->join('weeks', 'weeks.id', '=', 'd_surge.week_id')
			->join('view_facilitys', 'view_facilitys.id', '=', 'd_surge.facility')
			->selectRaw($sql)
			->when(true, $this->get_callback())
			->get();


		foreach ($rows as $key => $row){
			$data['categories'][$key] = Lookup::get_category($row);

			foreach ($ages as $age_key => $age) {
				$t = $age->age . '_tested';
				$p = $age->age . '_pos';
				$data["outcomes"][$age_key]["data"][$key] = Lookup::get_percentage($row->$p, $row->$t);
			}
		}
		return view('charts.line_graph', $data);
	}*/


	// Yield by age
	public function age_yield()
	{
		$sql = '';

		$groupby = session('filter_groupby', 1);
		$data['div'] = str_random(15);
		$data['yAxis'] = "HTS Pos";
		$data['suffix'] = '';
		$data['stacking'] = true;
		$data['extra_tooltip'] = true;

		$ages = SurgeAge::when(session('filter_gender'), function($query){
						if(session('filter_gender') == 3) return $query->where('no_gender', 1);
					})->get();

		foreach ($ages as $key => $age) {
			$tested_columns = SurgeColumnView::where('age_id', $age->id)
				->where('column_name', 'like', '%tested%')
				->when(true, $this->surge_columns_callback(true, true, false))
				->get();

			$positive_columns = SurgeColumnView::where('age_id', $age->id)
				->where('column_name', 'like', '%positive%')
				->when(true, $this->surge_columns_callback(true, true, false))
				->get();

			$sql .= $this->get_sum($tested_columns, $age->age . '_tested') . ', ' . $this->get_sum($positive_columns, $age->age . '_pos') . ', ';

			$data['outcomes'][$key]['name'] = $age->age_name;
			$data['outcomes'][$key]['type'] = "column";
		}

		$sql = substr($sql, 0, -2);

		$rows = DB::table('d_surge')
			->join('weeks', 'weeks.id', '=', 'd_surge.week_id')
			->join('view_facilitys', 'view_facilitys.id', '=', 'd_surge.facility')
			->selectRaw($sql)
			->when(true, $this->get_callback())
			->get();


		foreach ($rows as $key => $row){
			$data['categories'][$key] = Lookup::get_category($row);

			foreach ($ages as $age_key => $age) {
				$t = $age->age . '_tested';
				$p = $age->age . '_pos';
				$data["outcomes"][$age_key]["data"][$key]['y'] = (int) $row->$p;
				$data["outcomes"][$age_key]["data"][$key]['z'] = ', yield of ' .  Lookup::get_percentage($row->$p, $row->$t) . '%';
			}
		}
		return view('charts.line_graph', $data);
	}


	// Yield by gender
	public function gender_yield()
	{
		$sql = '';

		$groupby = session('filter_groupby', 1);
		$data['div'] = str_random(15);
		$data['yAxis'] = "Yield by Gender (%)";
		$data['suffix'] = '%';

		$genders = SurgeGender::where('id', '!=', 3)->get();

		foreach ($genders as $key => $gender) {
			$tested_columns = SurgeColumnView::where('gender_id', $gender->id)
				->where('column_name', 'like', '%tested%')
				->when(true, $this->surge_columns_callback(true, false, true))
				->get();

			$positive_columns = SurgeColumnView::where('gender_id', $gender->id)
				->where('column_name', 'like', '%positive%')
				->when(true, $this->surge_columns_callback(true, false, true))
				->get();

			$sql .= $this->get_sum($tested_columns, $gender->gender . '_tested') . ', ' . $this->get_sum($positive_columns, $gender->gender . '_pos') . ', ';

			$data['outcomes'][$key]['name'] = $gender->gender;
			$data['outcomes'][$key]['type'] = "column";
		}

		$sql = substr($sql, 0, -2);

		$rows = DB::table('d_surge')
			->join('weeks', 'weeks.id', '=', 'd_surge.week_id')
			->join('view_facilitys', 'view_facilitys.id', '=', 'd_surge.facility')
			->selectRaw($sql)
			->when(true, $this->get_callback())
			->get();


		foreach ($rows as $key => $row){
			$data['categories'][$key] = Lookup::get_category($row);

			foreach ($genders as $gender_key => $gender) {
				$t = $gender->gender . '_tested';
				$p = $gender->gender . '_pos';
				$data["outcomes"][$gender_key]["data"][$key] = Lookup::get_percentage($row->$p, $row->$t);
			}
		}
		return view('charts.line_graph', $data);
	}

	// PNS for surge
	public function pns()
	{
		$sql = '';

		$groupby = session('filter_groupby', 1);
		$data['div'] = str_random(15);
		$data['yAxis'] = "PNS Totals";
		$data['suffix'] = '';
		// $data['stacking_false'] = false;

		$pns_array = ['clients_screened', 'contacts_identified', 'pos_contacts', 'eligible_contacts', 'contacts_tested', 'new_pos', 'linked_to_haart'];

		$pns_modalities = SurgeModality::whereIn('modality', $pns_array)->orderBy('id', 'asc')->get();

		foreach ($pns_modalities as $key => $pns) {
			$sql .= $this->get_pns_sum($pns->modality) . ', ';
			$data['outcomes'][$key]['name'] = $pns->modality_name;
			$data['outcomes'][$key]['type'] = "column";
		}

		$sql = substr($sql, 0, -2);

		$rows = DB::table('d_surge')
			->join('weeks', 'weeks.id', '=', 'd_surge.week_id')
			->join('view_facilitys', 'view_facilitys.id', '=', 'd_surge.facility')
			->selectRaw($sql)
			->when(true, $this->get_callback())
			->get();

		foreach ($rows as $key => $row){
			$data['categories'][$key] = Lookup::get_category($row);

			foreach ($pns_array as $pns_key => $pns) {
				$data["outcomes"][$pns_key]["data"][$key] = (int) $row->$pns;
			}
		}
		return view('charts.line_graph', $data);
	}

	// TX SV for surge
	public function tx_sv()
	{
		$sql = '';

		$groupby = session('filter_groupby', 1);
		$data['div'] = str_random(15);
		$data['yAxis'] = "TX New Patients";
		$data['suffix'] = '';
		$data['stacking'] = true;

		$tx_sv_array = ['tx_sv_d', 'tx_sv_n'];

		$tx_sv_modalities = SurgeModality::whereIn('modality', $tx_sv_array)->orderBy('id', 'asc')->get();

		foreach ($tx_sv_modalities as $key => $tx_sv) {
			$sql .= $this->get_pns_sum($tx_sv->modality) . ', ';
			$data['outcomes'][$key]['type'] = "column";
		}
		$data['outcomes'][$key]['type'] = "spline";

		$data['outcomes'][0]['name'] = "TX New Second Visit Due but didn't show";
		$data['outcomes'][1]['name'] = "TX New Second Visit Number";
		$data['outcomes'][2]['name'] = "Retention";

		$data['outcomes'][0]['yAxis'] = 1;
		$data['outcomes'][1]['yAxis'] = 1;

		$data['outcomes'][0]['tooltip'] = array("valueSuffix" => ' ');
		$data['outcomes'][1]['tooltip'] = array("valueSuffix" => ' ');
		$data['outcomes'][2]['tooltip'] = array("valueSuffix" => ' %');

		$sql = substr($sql, 0, -2);

		$rows = DB::table('d_surge')
			->join('weeks', 'weeks.id', '=', 'd_surge.week_id')
			->join('view_facilitys', 'view_facilitys.id', '=', 'd_surge.facility')
			->selectRaw($sql)
			->when(true, $this->get_callback())
			->get();

		foreach ($rows as $key => $row){
			$data['categories'][$key] = Lookup::get_category($row);

			$data["outcomes"][0]["data"][$key] = (int) ($row->tx_sv_d - $row->tx_sv_n);
			$data["outcomes"][1]["data"][$key] = (int) $row->tx_sv_n;
			if($data["outcomes"][0]["data"][$key] < 0) $data["outcomes"][0]["data"][$key] = 0;
			$data["outcomes"][2]["data"][$key] = Lookup::get_percentage($row->tx_sv_n, ($data["outcomes"][0]["data"][$key] + $data["outcomes"][1]["data"][$key]));
		}
		return view('charts.dual_axis', $data);
	}




	public function get_sum($columns, $name)
	{
		$sql = "(";

		foreach ($columns as $column) {
			$sql .= "SUM(`{$column->column_name}`) + ";
		}
		$sql = substr($sql, 0, -3);
		$sql .= ") AS {$name} ";
		return $sql;
	}

	public function get_pns_sum($pns_name)
	{
		$pns_columns = SurgeColumn::where('column_name', 'LIKE', "{$pns_name}%")
			->when(true, $this->surge_columns_callback(false, true, true))
			->get();

		return $this->get_sum($pns_columns, $pns_name);
	}


	
	public function set_surge_facilities(Request $request)
	{
		$partner = session('session_partner');
		if(!$partner){
			$partner = auth()->user()->partner;
			session(['session_partner' => $partner]);
		}

		$facilities = $request->input('facilities');
		Facility::where('partner', $partner->id)->whereNotIn('id', $facilities)->update(['is_surge' => 0]);
		Facility::where('partner', $partner->id)->whereIn('id', $facilities)->update(['is_surge' => 1]);
		session(['toast_message' => 'The selected facilities have been set to surge facilities.']);
		return back();
	}


	public function download_excel(Request $request)
	{
		$partner = session('session_partner');
		if(!$partner){
			$partner = auth()->user()->partner;
			session(['session_partner' => $partner]);
		}
		$data = [];

		$week_id = $request->input('week');
		$modalities = $request->input('modalities');
		$gender = $request->input('gender');
		$ages = $request->input('ages');

		$columns = SurgeColumn::when(true, function($query) use ($modalities){
			if(is_array($modalities)) return $query->whereIn('modality_id', $modalities);
			return $query->where('modality_id', $modalities);
		})->when($gender, function($query) use ($gender){
			return $query->where('gender_id', $gender);
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

		$week = Week::find($week_id);
		$filename = str_replace(' ', '_', strtolower($partner->name)) . '_surge_data_for_' . $week->start_date . '_to_' . $week->end_date;

		$facilities = Facility::select('id')->where(['is_surge' => 1, 'partner' => $partner->id])->get()->pluck('id')->toArray();
		
		$rows = DB::table('d_surge')
			->join('view_facilitys', 'view_facilitys.id', '=', 'd_surge.facility')
			->join('weeks', 'weeks.id', '=', 'd_surge.week_id')
			->selectRaw($sql)
			->where('week_id', $week_id)
			->where('partner', $partner->id)
			->when($facilities, function($query) use ($facilities){
				return $query->whereIn('view_facilitys.id', $facilities);
			})
			->orderBy('name', 'asc')
			->get();

		foreach ($rows as $row) {
			$row_array = get_object_vars($row);
			$data[] = $row_array;
		}
    	$path = storage_path('exports/' . $filename . '.xlsx');
    	if(file_exists($path)) unlink($path);

    	Excel::create($filename, function($excel) use($data){
    		$excel->sheet('sheet1', function($sheet) use($data){
    			$sheet->fromArray($data);
    		});

    	})->store('xlsx');

    	return response()->download($path);
	}



	public function upload_excel(Request $request)
	{
		ini_set('memory_limit', '-1');
		if (!$request->hasFile('upload')){
	        session(['toast_message' => 'Please select a file before clicking the submit button.']);
	        session(['toast_error' => 1]);
			return back();
		}
		$file = $request->upload->path();

		$data = Excel::load($file, function($reader){
			$reader->toArray();
		})->get();

		// dd($data);

		$partner = session('session_partner');
		
		if(!$partner){
			$partner = auth()->user()->partner;
			session(['session_partner' => $partner]);
		}

		$today = date('Y-m-d');

		$surge_columns = SurgeColumn::all();

		$columns = [];
		$week = null;

		foreach ($surge_columns as $key => $value) {
			$columns[$value->excel_name] = $value->column_name;
		}

		foreach ($data as $row_key => $row){
			if(!is_numeric($row->mfl_code) || (is_numeric($row->mfl_code) && $row->mfl_code < 10000)) continue;
			$fac = Facility::where('facilitycode', $row->mfl_code)->first();
			if(!$fac) continue;
			// if(!$fac) dd('Facility not found');

			if(!$week) $week = Week::where(['financial_year' => $row->financial_year, 'week_number' => $row->week_number])->first();

			$update_data = ['dateupdated' => $today];

			foreach ($row as $key => $value) {
				if(isset($columns[$key])){
					$update_data[$columns[$key]] = (int) $value;
				}
			}

			// DB::enableQueryLog();

			DB::connection('mysql_wr')->table('d_surge')
				->where(['facility' => $fac->id, 'week_id' => $week->id])
				->update($update_data);

	 		// return DB::getQueryLog();
		}

		session(['toast_message' => "The surge updates have been made."]);
		return back();
	}
}
