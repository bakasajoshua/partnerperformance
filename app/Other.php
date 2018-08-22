<?php

namespace App;

use DB;
use App\Synch;

class Other extends Model
{

	public static function other_targets()
	{
		$table_name = 't_non_mer';
    	$sql = "CREATE TABLE `{$table_name}` (
    				id int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    				facility int(10) UNSIGNED DEFAULT 0,
    				financial_year smallint(4) UNSIGNED DEFAULT 0,
    				viremia_beneficiaries int(10) DEFAULT NULL,
    				viremia_target int(10) DEFAULT NULL,
    				dsd_beneficiaries int(10) DEFAULT NULL,
    				dsd_target int(10) DEFAULT NULL,
    				otz_beneficiaries int(10) DEFAULT NULL,
    				otz_target int(10) DEFAULT NULL,
    				men_clinic_beneficiaries int(10) DEFAULT NULL,
    				men_clinic_target int(10) DEFAULT NULL,

	        		dateupdated date DEFAULT NULL,
					PRIMARY KEY (`id`),
					KEY `identifier`(`facility`, `financial_year`),
					KEY `facility` (`facility`)
				);
        ";
        DB::connection('mysql_wr')->statement("DROP TABLE IF EXISTS `{$table_name}`;");
        DB::connection('mysql_wr')->statement($sql);
	}

	public static function insert_others($year)
	{
		$table_name = 't_non_mer';
		$i=0;
		$data_array = [];
		
		$facilities = Facility::select('id')->get();
		foreach ($facilities as $k => $val) {
			$data_array[$i] = array('financial_year' => $year, 'facility' => $val->id);
			$i++;

			if ($i == 200) {
				DB::connection('mysql_wr')->table($table_name)->insert($data_array);
				$data_array=null;
		    	$i=0;
			}
		}

		if($data_array) DB::connection('mysql_wr')->table($table_name)->insert($data_array);

	}

	public static function partner_targets()
	{
		$table_name = 'p_non_mer';
    	$sql = "CREATE TABLE `{$table_name}` (
    				id int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    				partner tinyint(3) UNSIGNED DEFAULT 0,
    				financial_year smallint(4) UNSIGNED DEFAULT 0,
    				viremia int(10) DEFAULT NULL,
    				dsd int(10) DEFAULT NULL,
    				otz int(10) DEFAULT NULL,
    				men_clinic int(10) DEFAULT NULL,
	        		dateupdated date DEFAULT NULL,
					PRIMARY KEY (`id`),
					KEY `identifier`(`partner`, `financial_year`),
					KEY `partner` (`partner`)
				);
        ";
        DB::connection('mysql_wr')->statement("DROP TABLE IF EXISTS `{$table_name}`;");
        DB::connection('mysql_wr')->statement($sql);
	}

	public static function insert_partner_nonmer($year)
	{
		$table_name = 'p_non_mer';
		$i=0;
		$data_array = [];
		
		$partners = Partner::select('id')->get();
		foreach ($partners as $k => $val) {
			$data_array[$i] = array('financial_year' => $year, 'partner' => $val->id);
			$i++;

			if ($i == 200) {
				DB::connection('mysql_wr')->table($table_name)->insert($data_array);
				$data_array=null;
		    	$i=0;
			}
		}

		if($data_array) DB::connection('mysql_wr')->table($table_name)->insert($data_array);
	}

	public static function partner_indicators()
	{
		$table_name = 'p_early_indicators';
    	$sql = "CREATE TABLE `{$table_name}` (
    				id int(10) UNSIGNED NOT NULL AUTO_INCREMENT,

    				partner tinyint(3) UNSIGNED DEFAULT 0,
    				county tinyint(3) UNSIGNED DEFAULT 0,

    				year smallint(4) UNSIGNED DEFAULT 0,
    				month tinyint(3) UNSIGNED DEFAULT 0,
    				financial_year smallint(4) UNSIGNED DEFAULT 0,
    				quarter tinyint(3) UNSIGNED DEFAULT 0,

					tested int(10) DEFAULT NULL,    				
					positive int(10) DEFAULT NULL,    				
					new_art int(10) DEFAULT NULL,    				
					linkage int(10) DEFAULT NULL,    				



	        		dateupdated date DEFAULT NULL,
					PRIMARY KEY (`id`),
					KEY `specific_identifier`(`partner`, `county`, `year`, `month`),
					KEY `identifier`(`partner`, `county`, `financial_year`, `quarter`),
					KEY `p_identifier`(`partner`, `financial_year`, `quarter`),
					KEY `c_identifier`(`county`, `financial_year`, `quarter`),
					KEY `partner` (`partner`),
					KEY `county` (`county`),
				);
        ";
        DB::connection('mysql_wr')->statement("DROP TABLE IF EXISTS `{$table_name}`;");
        DB::connection('mysql_wr')->statement($sql);
	}

	public static function partner_indicators($year=null)
	{
		if(!$year) $year = date('Y');
		$table_name = 'p_early_indicators';

        $partners = DB::('partners')->get();
        $counties = DB::('countys')->get();

		$i=0;
		$data_array = [];

		for ($month=1; $month < 13; $month++) { 
			$fq = Synch::get_financial_year_quarter($year, $month);
			foreach ($partners as $partner) {
				foreach ($counties as $county) {
					$data = ['year' => $year, 'month' => $month, 'partner' => $partner->id, 'county' => $county->id];
					$data = array_merge($data, $fq);
				}
			}
		}



	}

}
