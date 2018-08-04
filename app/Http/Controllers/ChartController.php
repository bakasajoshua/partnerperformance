<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use App\Lookup;

class ChartController extends Controller
{
	public function treatment()
	{

	}

	public function current()
	{
		$date_query = Lookup::date_query();
		$divisions_query = Lookup::divisions_query();

		$rows = DB::table('d_hiv_and_tb_treatment')
			->join('view_facilitys', 'view_facilitys.id', '=', 'd_hiv_and_tb_treatment.facility')
			->selectRaw("SUM(`on_art_total_(sum_hv03-034_to_hv03-043)_hv03-038`) AS `total`")
			->addSelect('year', 'month')
			->whereRaw($date_query)
			->whereRaw($divisions_query)
			->groupBy('year', 'month')
			->orderBy('year', 'asc')
			->orderBy('month', 'asc')
			->get();

		$target = DB::table('t_hiv_and_tb_treatment')
			->join('view_facilitys', 'view_facilitys.id', '=', 't_hiv_and_tb_treatment.facility')
			->selectRaw("SUM(`on_art_total_(sum_hv03-034_to_hv03-043)_hv03-038`) AS `total`")
			->whereRaw($date_query)
			->whereRaw($divisions_query)
			->first();

		$data['div'] = str_random(15);

		$t = round(($target->total / 12), 2);

		$data['outcomes'][0]['name'] = "Totals";
		$data['outcomes'][1]['name'] = "Target";

		$data['outcomes'][0]['type'] = "column";
		$data['outcomes'][1]['type'] = "spline";

		$data['outcomes'][0]['yAxis'] = 1;

		$data['outcomes'][0]['tooltip'] = array("valueSuffix" => ' ');
		$data['outcomes'][1]['tooltip'] = array("valueSuffix" => ' ');
		// $data['outcomes'][2]['tooltip'] = array("valueSuffix" => ' %');

		foreach ($rows as $key => $row) {
			$m = Lookup::resolve_month($row->month);
			$data['categories'][$key] = substr($m, 0, 3) . ', ' . $row->year;
			$data["outcomes"][0]["data"][$key] = (int) $row->total;
			$data["outcomes"][1]["data"][$key] = $t;
		}

		return view('charts.dual_axis', $data);
	}

	public function art_new()
	{
		$date_query = Lookup::date_query();
		$divisions_query = Lookup::divisions_query();

		$rows = DB::table('d_hiv_and_tb_treatment')
			->join('view_facilitys', 'view_facilitys.id', '=', 'd_hiv_and_tb_treatment.facility')
			->selectRaw("SUM(`start_art_total_(sum_hv03-018_to_hv03-029)_hv03-026`) AS `total`")
			->addSelect('year', 'month')
			->whereRaw($date_query)
			->whereRaw($divisions_query)
			->groupBy('year', 'month')
			->orderBy('year', 'asc')
			->orderBy('month', 'asc')
			->get();

		$target = DB::table('t_hiv_and_tb_treatment')
			->join('view_facilitys', 'view_facilitys.id', '=', 't_hiv_and_tb_treatment.facility')
			->selectRaw("SUM(`start_art_total_(sum_hv03-018_to_hv03-029)_hv03-026`) AS `total`")
			->whereRaw($date_query)
			->whereRaw($divisions_query)
			->first();

		$data['div'] = str_random(15);

		$t = round(($target->total / 12), 2);

		$data['outcomes'][0]['name'] = "Totals";
		$data['outcomes'][1]['name'] = "Target";

		$data['outcomes'][0]['type'] = "column";
		$data['outcomes'][1]['type'] = "spline";

		$data['outcomes'][0]['yAxis'] = 1;

		$data['outcomes'][0]['tooltip'] = array("valueSuffix" => ' ');
		$data['outcomes'][1]['tooltip'] = array("valueSuffix" => ' ');
		// $data['outcomes'][2]['tooltip'] = array("valueSuffix" => ' %');

		foreach ($rows as $key => $row) {
			$m = Lookup::resolve_month($row->month);
			$data['categories'][$key] = substr($m, 0, 3) . ', ' . $row->year;
			$data["outcomes"][0]["data"][$key] = (int) $row->total;
			$data["outcomes"][1]["data"][$key] = $t;
		}

		return view('charts.dual_axis', $data);
	}

	public function testing_gender()
	{
		$date_query = Lookup::date_query();
		$divisions_query = Lookup::divisions_query();

		$row = DB::table('d_hiv_testing_and_prevention_services')
			->join('view_facilitys', 'view_facilitys.id', '=', 'd_hiv_testing_and_prevention_services.facility')
			->selectRaw($this->gender_query())
			->addSelect('year', 'month')
			->whereRaw($date_query)
			->whereRaw($divisions_query)
			->first();

		$data['paragraph'] = "
		<table class='table table-striped'>
			<tr>
				<td>Below 10 : </td> <td>" . number_format($row->below_10_test) . "</td>
			</tr>
			<tr>
				<td>Male : </td> <td>" . number_format($row->male_test) . "</td>
			</tr>
			<tr>
				<td>Female : </td> <td>" . number_format($row->female_test) . "</td>
			</tr>
			<tr>
				<td>Total : </td> <td>" . number_format($row->below_10_test + $row->male_test + $row->female_test) . "</td>
			</tr>
		</table>			
		";

		$data['div'] = str_random(15);

		$data['outcomes']['data'][0]['name'] = "Male";
		$data['outcomes']['data'][1]['name'] = "Female";

		$data['outcomes']['data'][0]['y'] = (int) $row->male_test;
		$data['outcomes']['data'][1]['y'] = (int) $row->female_test;

		return view('charts.pie_chart', $data);
	}

    public function gender_query()
    {
    	return "
			SUM(`tested_1-9_hv01-01`) as below_10_test,
    		SUM(`tested_10-14_(m)_hv01-02` + `tested_15-19_(m)_hv01-04` + `tested_20-24(m)_hv01-06` + `tested_25pos_(m)_hv01-08`) AS male_test,
    		SUM(`tested_10-14(f)_hv01-03` + `tested_15-19(f)_hv01-05` + `tested_20-24(f)_hv01-07` + `tested_25pos_(f)_hv01-09`) AS female_test,
			SUM(`positive_1-9_hv01-17`) as below_10_pos,
			SUM(`positive_10-14(m)_hv01-18` + `positive_15-19(m)_hv01-20` + `positive_20-24(m)_hv01-22` + `positive_25pos(m)_hv01-24`) as male_pos,
			SUM(`positive_10-14(f)_hv01-19` + `positive_15-19(f)_hv01-21` + `positive_20-24(f)_hv01-23` + `positive_25pos(f)_hv01-25`) as female_pos
		";
    }

    public function age_query()
    {
    	return "
    		SUM(`tested_1-9_hv01-01`) as below_10,
			SUM(`tested_10-14_(m)_hv01-02` + `tested_10-14(f)_hv01-03`) as below_15,
			SUM(`tested_15-19_(m)_hv01-04` + `tested_15-19(f)_hv01-05`) as below_20,
			SUM(`tested_20-24(m)_hv01-06` + `tested_20-24(f)_hv01-07`) as below_25,
			SUM(`tested_25pos_(m)_hv01-08` + `tested_25pos_(f)_hv01-09`) as above_25,

			SUM(`positive_1-9_hv01-17`) as below_10_pos,
			SUM(`positive_10-14(m)_hv01-18` + `positive_10-14(f)_hv01-19`) as below_15_pos,
			SUM(`positive_15-19(m)_hv01-20` + `positive_15-19(f)_hv01-21`) as below_20_pos,
			SUM(`positive_20-24(m)_hv01-22` + `positive_20-24(f)_hv01-23`) as below_25_pos,
			SUM(`positive_25pos(m)_hv01-24` + `positive_25pos(f)_hv01-25`) as above_25_pos,
    	";
    }
}
