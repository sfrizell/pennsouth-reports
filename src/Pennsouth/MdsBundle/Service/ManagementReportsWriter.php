<?php
/**
 * ManagementReportsWriter.php
 * User: sfrizell
 * Date: 11/27/16
 *  Function:
 */

namespace Pennsouth\MdsBundle\Service;

use Doctrine\ORM\EntityManager;
use Pennsouth\MdsBundle\Entity\PennsouthResident;
use PHPExcel_Style_Fill;
use PHPExcel;
use PHPExcel_Cell;

class ManagementReportsWriter
{

    const PARKING_LOT_LIST_FILE_NAME = 'parking_lot_spaces.xlsx';
    const PARKING_LOT_LIST_BATCH_SIZE = 2000;
    const OBJECT1_COLS_NUM = 14;
    const HOMEOWNERS_INSURANCE_REPORT_FILE_NAME = 'homeowners_insurance_report.csv';
    const INCOME_AFFIDAVIT_REPORT_FILE_NAME = 'income_affidavit_report.csv';
    const MDS_DATA_ENTRY_GAPS_REPORT_FILE_NAME = 'mds_data_entry_gaps_report.csv';
    const PENNSOUTH_SHAREHOLDERS_REPORT_FILE_NAME = 'pennsouth_shareholders_report.csv';


    const PARKING_LOT_LIST_HEADER_ARRAY = array(
                'Building',
                'Floor Number',
                'Apt Line',
                'Apt. Surrendered',
                'No Email for Entire Apt.',
                'No Email for Shareholders',
                'Parking Lot',
                'Decal Num',
                'Gap',
                'Car Reg. Expiration Date (MDS)',
                'Days Until Car Reg. Expires',
                'Car Reg. Exp. Countdown Interval',
                'Make/Model',
                'License Plate',
                'Shareholder1 Last Name',
                'Shareholder1 First Name',
                'Shareholder1 Email',
                'Shareholder1 Cell',
                'Shareholder1 Home Phone',
                'Resident Category',
                'Shareholder2 Last Name',
                'Shareholder2 First Name',
                'Shareholder2 Email',
                'Shareholder2 Cell',
                'Shareholder2 Home Phone',
                'Address',
                'City',
                'State',
                'Zip'
       );

       const PARKING_LOT_LIST_COL_NAMES = array(
                    'building',
                    'floor_number',
                    'apt_line',
                    'apt_surrendered',
                    'no_email_for_apartment',
                    'no_email_for_shareholders',
                    'parking_lot_location',
                    'decal_num',
                    'gap',
                    'vehicle_reg_exp_date',
                    'vehicle_reg_exp_countdown',
                    'vehicle_reg_interval_remaining',
                    'vehicle_model',
                    'vehicle_license_plate_num',
                    'last_name',
                    'first_name',
                    'email_address',
                    'cell_phone',
                    'evening_phone',
                    'mds_resident_category2',
                    'last_name2',
                    'first_name2',
                    'email_address2',
                    'cell_phone2',
                    'evening_phone2',
                    'address',
                    'city',
                    'state',
                    'zip'
           );

    const HOMEOWNERS_INSURANCE_REPORT_HEADER_ARRAY = array(
                    'Building',
                    'Address',
                    'City, State Zip',
                    'Floor Number',
                    'Apt Line',
                    'Apt. Surrendered',
                    '2nd Resident Category',
                    'Homeowners Insurance Exp Date (MDS)',
                    'Days Until Reg. Expires',
                    'Expiration Interval Remaining',
                    'No Email for Entire Apt.',
                    'No Email for Shareholders',
                    'Shareholder1 Last Name',
                    'Shareholder1 First Name',
                    'Shareholder1 Email',
                    'Shareholder Status',
                    'Shareholder1 Cell',
                    'Shareholder1 Home Phone',
                    'Shareholder2 Last Name',
                    'Shareholder2 First Name',
                    'Shareholder2 Email',
                    'Shareholder2 Cell',
                    'Shareholder2 Home Phone'
           );

    const HOMEOWNERS_INSURANCE_REPORT_COL_NAMES = array(
                     'building',
                     'address',
                     'city_state_zip',
                     'floor_number',
                     'apt_line',
                     'apt_surrendered',
                     'mds_2nd_resident_category',
                     'homeowner_ins_exp_date',
                     'homeowner_ins_exp_countdown,',
                     'homeowner_ins_interval_remaining',
                     'no_email_for_apartment',
                     'no_email_for_shareholders',
                     'last_name',
                     'first_name',
                     'email_address',
                     'shareholder_status',
                     'cell_phone',
                     'evening_phone',
                     'last_name2',
                     'first_name2',
                     'email_address2',
                     'cell_phone2',
                     'evening_phone2'
            );

    const INCOME_AFFIDAVIT_REPORT_HEADER_ARRAY = array(
                    'Building',
                    'Address',
                    'City, State Zip',
                    'Floor Number',
                    'Apt Line',
                    'Apt Surrendered',
                    'No Email for Entire Apt.',
                    'No Email for Shareholders',
                    'Shareholder1 Last Name',
                    'Shareholder1 First Name',
                    'Shareholder1 Email',
                    'Shareholder1 Cell',
                    'Shareholder1 Home Phone',
                    'Resident Category',
                    'Shareholder2 Last Name',
                    'Shareholder2 First Name',
                    'Shareholder2 Email',
                    'Shareholder2 Cell',
                    'Shareholder2 Home Phone',
                    'Income Affidavit Receipt Date',
                    'Income Affidavit Received',
                    'Income Affidavit Receipt Date Discrepancy',
                    'Income Affidavit 1st Deadline',
                    'Income Affidavit 2nd Deadline',
                    'Late Charge Missed 1st Deadline',
                    'Late Charge Missed 2nd Deadline',
                    'Move-In Date'
           );

    const INCOME_AFFIDAVIT_REPORT_COL_NAMES = array(
                     'building',
                     'address',
                     'city_state_zip',
                     'floor_number',
                     'apt_line',
                     'apt_surrendered',
                     'no_email_for_apartment',
                     'no_email_for_shareholders',
                     'last_name',
                     'first_name',
                     'mds_export_email_address',
                     'cell_phone',
                     'evening_phone',
                     'mds_resident_category',
                     'last_name2',
                     'first_name2',
                     'mds_export_email_address2',
                     'cell_phone2',
                     'evening_phone2',
                     'inc_affidavit_receipt_date',
                     'inc_affidavit_received',
                     'inc_affidavit_date_discrepancy',
                     'first_annual_deadline',
                     'second_annual_deadline',
                     'late_charge1',
                     'late_charge2',
                     'move_in_date'
            );
    // mds data entry discrepancies report
    const MDS_GAPS_REPORT_HEADER_ARRAY = array(
                        'Building',
                        'Floor Number',
                        'Apt Line',
                        'Shareholder Last Name',
                        'Shareholder First Name',
                        'Shareholder Daytime Phone',
                        'Shareholder Cell Phone',
                        'Shareholder Evening Phone',
                        'Shareholder1 Email',
                        'Resident Category',
                        'Shareholder Flag',
                        'MDS Export File Category',
                        'Apt. Surrendered'
               );

        const MDS_GAPS_REPORT_COL_NAMES = array(
                         'building',
                         'floor_number',
                         'apt_line',
                         'no_email_for_apartment',
                         'no_email_for_shareholders',
                         'last_name',
                         'first_name',
                         'daytime_phone',
                         'evening_phone',
                         'cell_phone',
                         'email_address',
                         'mds_resident_category',
                         'shareholder_flag',
                         'mds_export_category',
                         'apt_surrendered'
                );
    const PENNSOUTH_RESIDENT_REPORT_HEADER_ARRAY = array(
                               'Resident Id',
                               'Apt Id',
                               'Building',
                               'Floor Number',
                               'Apt Line',
                               'Building-Floor-Apt',
                               'Last Name',
                               'First Name',
                               'Email',
                               'Resident Category',
                               'Daytime Phone',
                               'Evening Phone',
                               'Cell Phone',
                               'Person Id',
                               'Toddler Room Member',
                               'Youth Room Member',
                               'Ceramics Member',
                               'Woodworking Member',
                               'Gym Member',
                               'Garden Member',
                               'Decal Num.',
                               'Parking Lot Location',
                               'Vehicle Reg. Exp. Date',
                               'Vehicle Reg. Exp. Countdown',
                               'Vehicle Reg. Interval Remaining',
                               'Vehicle Model',
                               'Vehicle License Plate Num',
                               'Homeowners Ins. Exp. Date',
                               'Homeowners Ins. Exp. Countdown',
                               'Homeowners Ins. Interval Remaining',
                               'Birth Date',
                               'Move-In Date',
                               'Shareholder Flag',
                               'Inc. Affidavit Receipt Date',
                               'Inc. Affidavit Receive',
                               'Inc. Affidavit Discrepancy',
                               'Storage Locker Closet Bldg Num',
                               'Storage Locker Num',
                               'Storage Closet Floor Num',
                               'Dog Tag Num',
                               'Is Dog In Apt',
                               'Bike Rack Location',
                               'Bike Rack Bldg',
                               'Bike Rack Room',
                               'Apt Surrendered',
                               'MDS Export-Id',
                               'HPerson-Id',
                               'Last Changed Date'
                      );


    private $entityManager;
    private $phpExcel;
    private $appOutputDir;
    private $env;

    public function __construct (EntityManager $entityManager,  $phpExcel = null, $appOutputDir, $env ) {

        $this->entityManager    = $entityManager;
        $this->phpExcel         = $phpExcel;
        $this->appOutputDir     = $appOutputDir;
        $this->env              = $env;

    }

    public function getEntityManager() {
        return $this->entityManager;
    }


    /**
     *   * Run SQL query against the pennsouth_db.pennsouth_resident table to obtain a list of all distinct apartments
     *   that have assigned Pennsouth parking lot spaces. Obtain the list in order by decal_num. Write
     *   the list to an excel spreadsheet, identifying any gaps in the sequence of assigned values for decal_num.
     *   These gaps identify unfilled parking spaces.
     *   Write the spreadsheet to the /app_output directory under the project root directory.
     * See: http://stackoverflow.com/questions/39186017/creating-excel-file-from-array-using-php-and-symfony
     *   also: http://ourcodeworld.com/articles/read/50/how-to-create-a-excel-file-with-php-in-symfony-2-3
     * For usage examples of Font and Fill, see the comments in the code of PHPExcel:
     *    vendor/phpoffice/phpexcel/Classes/PHPExcel/Style/Font
     *    vendor/phpoffice/phpexcel/Classes/PHPExcel/Style/Fill
     *
     * @return bool
     */
    public function generateParkingLotList() {

        $residentsWithParkingSpaces = $this->getResidentsWithParkingSpaces();

        $title              = 'Pennsouth Parking Lot List';
        $description        = 'List of Parking Lot Spaces Assigned to Pennsouth Residents';
        $category           = 'List Management Reports';
        $spreadsheetTabName = 'Parking Lot List';

        $phpExcelObject = $this->getPhpExcelObjectAndSetHeadings(self::PARKING_LOT_LIST_HEADER_ARRAY, $title, $description, $category);

        if (!is_null($phpExcelObject) and $phpExcelObject instanceof PHPExcel) {

           // $fileWriteCtr = 0;


           // print("\n After setting autosize=true \n");

            $rowCtr = 1;

            $colLimit = count(self::PARKING_LOT_LIST_COL_NAMES);
            $phpExcelObject->setActiveSheetIndex(0);
            $prevDecalNum = null;
            $prevBuilding = null;
            $prevFloorNum = null;
            $prevAptLine = null;
            foreach ($residentsWithParkingSpaces as $row) {

                $gapMsg = '';
                  if (!is_null($prevDecalNum) and !($row['decal_num'] == 700)) {
                      $gapCalc = $row['decal_num'] - $prevDecalNum;
                      if ($gapCalc > 1) {
                          $gapMsg = "Gap";
                      }
                  }

            //    foreach ( $colNamesByHeaderNameKeys as $key => $value ) {
            //        print ("\n key: " . $key . " value: " . $value . "\n");

                // $row[0]->getName();

                    if ( is_null($prevBuilding) or
                          $row['building']      !== $prevBuilding   or
                          $row['floor_number']  !== $prevFloorNum   or
                          $row['apt_line']      !== $prevAptLine
                        ) {

                        $rowCtr++;
                        for ($i = 0; $i < $colLimit; $i++) {
                            $currentLetter = PHPExcel_Cell::stringFromColumnIndex($i);
                            $cellId = $currentLetter . $rowCtr;
                            //  print ("\$cellId: " . $cellId . "\n");
                            // if (!$key == 'Last Changed Date') {

                            //   $phpExcelObject->getActiveSheet()
                            //       ->setCellValue($cellId, $row[self::PARKING_LOT_LIST_COL_NAMES[$i]]);
                            $fieldName = 'get' . self::PARKING_LOT_LIST_COL_NAMES[$i] . '()';
                            if (self::PARKING_LOT_LIST_COL_NAMES[$i] == 'gap') {
                                $phpExcelObject->getActiveSheet()
                                    ->setCellValue($cellId, $gapMsg);
                            } else {
                                $phpExcelObject->getActiveSheet()
                                    ->setCellValue($cellId, $row[self::PARKING_LOT_LIST_COL_NAMES[$i]]);
                            }
                            // }
                        }
                    }

                    $prevDecalNum   = $row['decal_num'];
                    $prevBuilding   = $row['building'];
                    $prevFloorNum   = $row['floor_number'];
                    $prevAptLine    = $row['apt_line'];

            // }



            }

            $phpExcelObject->getActiveSheet()->setTitle($spreadsheetTabName);
            // Set active sheet index to the first sheet, so Excel opens this as the first sheet
            $phpExcelObject->setActiveSheetIndex(0);

            // create the writer
            $writer = $this->phpExcel->createWriter($phpExcelObject, 'Excel2007');
            // The save method is documented in the official PHPExcel library
            //$fileWriteCtr++;
            $writer->save($this->appOutputDir . '/' . self::PARKING_LOT_LIST_FILE_NAME);

            return TRUE;
        }

    return FALSE;

    }

    /**
     *
     * @return bool
     */
    public function createHomeownersInsuranceReport() {

        $homeownersInsuranceReportRows = $this->getHomeownersInsuranceReportRows();

       // $totalCols = count(self::HOMEOWNERS_INSURANCE_REPORT_HEADER_ARRAY)+1;

        $file = fopen($this->appOutputDir . "/" . self::HOMEOWNERS_INSURANCE_REPORT_FILE_NAME, "w");

        fputcsv($file, self::HOMEOWNERS_INSURANCE_REPORT_HEADER_ARRAY);

        $prevBuilding = null;
        $prevFloorNum = null;
        $prevAptLine = null;
        foreach($homeownersInsuranceReportRows as $row){
            if ( is_null($prevBuilding) or
                   $row['building']      !== $prevBuilding   or
                   $row['floor_number']  !== $prevFloorNum   or
                   $row['apt_line']      !== $prevAptLine
                 ) {
                fputcsv($file, $row);
            }
            $prevBuilding   = $row['building'];
            $prevFloorNum   = $row['floor_number'];
            $prevAptLine    = $row['apt_line'];
        }
        fclose($file);


        return TRUE;

    }

    /**
       *
       * @return bool
       */
      public function createIncomeAffidavitReport() {

          $incomeAffidavitReportRows = $this->getIncomeAffidavitReportRows();

         // $totalCols = count(self::HOMEOWNERS_INSURANCE_REPORT_HEADER_ARRAY)+1;

          $file = fopen($this->appOutputDir . "/" . self::INCOME_AFFIDAVIT_REPORT_FILE_NAME, "w");

          fputcsv($file, self::INCOME_AFFIDAVIT_REPORT_HEADER_ARRAY);

          $prevBuilding = null;
          $prevFloorNum = null;
          $prevAptLine = null;
          foreach($incomeAffidavitReportRows as $row){
              if ( is_null($prevBuilding) or
                     $row['building']      !== $prevBuilding   or
                     $row['floor_number']  !== $prevFloorNum   or
                     $row['apt_line']      !== $prevAptLine
                   ) {
                  fputcsv($file, $row);
              }
              $prevBuilding   = $row['building'];
              $prevFloorNum   = $row['floor_number'];
              $prevAptLine    = $row['apt_line'];
          }
          fclose($file);


          return TRUE;

      }

    /**
       *
       * @return bool - TRUE: data entry gaps/errors found
     *                  FALSE: no data entry gaps/errors found
       */
      public function createMdsDataEntryGapsReport() {

          $dataEntryGapsReportRows = $this->getMdsDataEntryGapsReportRows();


          $file = fopen($this->appOutputDir . "/" . self::MDS_DATA_ENTRY_GAPS_REPORT_FILE_NAME, "w");

          fputcsv($file, self::MDS_GAPS_REPORT_HEADER_ARRAY);

          $prevBuilding = null;
          $prevFloorNum = null;
          $prevAptLine = null;
          foreach($dataEntryGapsReportRows as $row){
                  fputcsv($file, $row);
          }
          fclose($file);

          if (count($dataEntryGapsReportRows) == 0) {
              return false; // no errors to report on; don't generate an email
          }
          else {
              return TRUE; // data entry gaps/errors were found; generate email with report as attachment
          }




      }


    public function createPennsouthResidentReport() {

         $pennsouthResidentReportRows = $this->getPennsouthResidentReportRows();


         $file = fopen($this->appOutputDir . "/" . self::PENNSOUTH_SHAREHOLDERS_REPORT_FILE_NAME, "w");

         fputcsv($file, self::PENNSOUTH_RESIDENT_REPORT_HEADER_ARRAY);

         $prevBuilding = null;
         $prevFloorNum = null;
         $prevAptLine = null;
         foreach($pennsouthResidentReportRows as $row){
                 fputcsv($file, $row);
         }
         fclose($file);

         if (count($pennsouthResidentReportRows) == 0) {
             return false; // no errors to report on; don't generate an email
         }
         else {
             return TRUE; // data entry gaps/errors were found; generate email with report as attachment
         }




     }

    private function getPhpExcelObjectAndSetHeadings($headerArray, $title, $description, $category) {

           $phpExcelObject = $this->phpExcel->createPHPExcelObject();



          if ($phpExcelObject instanceof PHPExcel) {

              $phpExcelObject->getProperties()->setCreator("batch")
                  ->setLastModifiedBy("Batch Process")
                  ->setTitle($title)
                  ->setSubject("Office 2005 XLSX Document")
                  ->setDescription($description)
                  ->setKeywords("office 2005 openxml php")
                  ->setCategory($category);


            //  $phpExcelStyleColor = new PHPExcel_Style_Color('EAE9DE');

              $totalCols = count($headerArray)+1;

              $phpExcelSheet = $phpExcelObject->getActiveSheet();
              $phpExcelSheet->fromArray($headerArray, NULL);
              $first_letter = PHPExcel_Cell::stringFromColumnIndex(0);
              $last_letter = PHPExcel_Cell::stringFromColumnIndex($totalCols);
              $header_range = "{$first_letter}1:{$last_letter}1";
              $phpExcelSheet->getStyle($header_range)->getFont()
                  ->setBold(true)
                  ->setSize(12)
                  ;

              $phpExcelSheet->getStyle($header_range)->getFill()->applyFromArray(
                                      array(
                                          'type' => PHPExcel_Style_Fill::FILL_SOLID,
                                          'color' => array(
                                              'rgb' => 'EAE9DE'
                                          )
                                      )
                                  );


                  foreach (range(0, $totalCols) as $col) {
                      $phpExcelObject->getActiveSheet()->getColumnDimensionByColumn($col)->setAutoSize(true);
                  }


              return $phpExcelObject;
          }

           return NULL;

       }

    /**
     * Use raw SQL rather than Doctrine DQL
     * @return array
     * @throws \Exception
     */
    private function getResidentsWithParkingSpaces() {


        /*
         * Following DQL doesn't work because of LEFT JOIN - don't know how to use custom join with Doctrine
         *                 'SELECT  distinct pr.building, pr.floorNumber, pr.aptLine, pr.mdsResidentCategory as mds_resident_category1,
                             pr.parkingLotLocation, \'\' as gap, pr.decalNum, pr.vehicleRegExpDate, pr.vehicleRegIntervalRemaining,
                             pr.vehicleModel, pr.vehicleLicensePlateNum,
                             pr.firstName, pr.lastName, pr.emailAddress, pr.cellPhone, pr.eveningPhone,
                             pr2.mdsResidentCategory, pr2.firstName, pr2.lastName, pr2.emailAddress,
                              pr2.cellPhone, pr2.eveningPhone
                          FROM PennsouthMdsBundle:PennsouthResident  pr
                             LEFT JOIN
                              PennsouthMdsBundle:PennsouthResident  pr2
                          ON
                             pr.building = pr2.building
                          and pr.floorNumber = pr2.floorNumber
                          and pr.aptLine	= pr2.aptLine
                          and pr.pennsouthResidentId < pr2.pennsouthResidentId
                          and pr.mdsResidentCategory = pr2.mdsResidentCategory
                          and concat(pr.firstName, pr.lastName) <> concat(pr2.firstName, pr2.lastName)
                          WHERE
                             pr.decalNum is not null and pr.mdsResidentCategory = :mdsResidentCategory
                                 order by pr.decalNum'
         *
         */

        try {
            /*
                       $query = $this->getEntityManager()->createQuery(
                'Select DISTINCT pr.building, pr.floorNumber, pr.aptLine,
                  pr.parkingLotLocation, pr.decalNum
                 from PennsouthMdsBundle:PennsouthResident pr
                where pr.decalNum is not NULL
                order by pr.decalNum'
            );

             */

            $query =
                'SELECT  distinct pr.building, pr.floor_number, pr.apt_line, pr.apt_surrendered, 
				if(apts_no_email.building_id is not null, \'No Email for Apartment\', \'\') no_email_for_apartment,
				if( length(trim(pr.email_address)) = 0 and (length(trim(pr2.email_address)) = 0 or pr2.email_address is null), \'No Email for Shareholders\', \'\') no_email_for_shareholders,
				pr.mds_resident_category as mds_resident_category1,
                pr.parking_lot_location, \'\' gap, cast(pr.decal_num as unsigned) decal_num, pr.vehicle_reg_exp_date, 
                if (length(pr.apt_surrendered) > 0, \'\', pr.vehicle_reg_exp_countdown) vehicle_reg_exp_countdown,
                if (length(pr.apt_surrendered) > 0, \'\', pr.vehicle_reg_interval_remaining) vehicle_reg_interval_remaining, 
                pr.vehicle_model, pr.vehicle_license_plate_num, 
                pr.last_name, pr.first_name, pr.email_address, pr.cell_phone, pr.evening_phone, 
                pr2.mds_resident_category as mds_resident_category2, pr2.last_name last_name2, pr2.first_name first_name2, 
                pr2.email_address email_address2, pr2.cell_phone cell_phone2, pr2.evening_phone evening_phone2,
                concat(pb.address, \', \', pr.floor_number, \'-\', pr.apt_line) address, pb.city, pb.state, pb.zip
             FROM pennsouth_resident as pr
                LEFT JOIN
                 pennsouth_resident as pr2
             ON
                pr.building = pr2.building
             and pr.floor_number = pr2.floor_number
             and pr.apt_line	= pr2.apt_line
             and pr.pennsouth_resident_id < pr2.pennsouth_resident_id
             and pr.mds_resident_category = pr2.mds_resident_category
             and concat(pr.first_name, pr.last_name) <> concat(pr2.first_name, pr2.last_name)
                LEFT JOIN
				missing_email_apt apts_no_email
			ON
				pr.building = apts_no_email.building_id
            and pr.floor_number = apts_no_email.floor_number
            and pr.apt_line		= apts_no_email.apt_line
				JOIN
                 pennsouth_bldg pb
			ON pr.building = pb.building_id
             WHERE
                pr.decal_num is not null and pr.mds_resident_category = :mdsResidentCategory
             order by cast(pr.decal_num as unsigned), pr2.mds_resident_category desc ';

            
            $statement = $this->getEntityManager()->getConnection()->prepare($query);
            // Set parameters
            $statement->bindValue( 'mdsResidentCategory', 'SHAREHOLDER');

            $statement->execute();

            $residentsWithParkingSpaces = $statement->fetchAll();

            return $residentsWithParkingSpaces;
        }
        catch (\Exception $exception) {
            print("\n" . "Fatal Exception occurred in ManagementReportsWriter->getResidentsWithParkingSpaces! ");
            print ("\n Exception->getMessage() : " . $exception->getMessage());
            print "Type: " . $exception->getCode(). "\n";
            print("\n" . "Exiting from program.");
            throw $exception;
        }

    }

    /**
     * NOTE: 10/29/2018 - can remove the select with reference to aweber_mds_sync_audit without any harm...
     *       11/4/2018 - SQL modified to include ALL pennsouth apartments - UNION of 2 selects
     *          - 1) returns apts where there is no designated shareholder
     *          - 2) returns apts having a designated shareholder (the vast majority fall in this category)
     * @return array
     * @throws \Exception
     */
    private function getHomeownersInsuranceReportRows() {

        try {

            $query =
                      'select pr3.building, b1.address, concat( b1.city, \', \', b1.state, \' \', b1.zip) city_state_zip, 
                           pr3.floor_number, pr3.apt_line, pr3.apt_surrendered, pr3.mds_resident_category mds_2nd_resident_category,
                           pr3.homeowner_ins_exp_date,                        
                           if (length(pr3.apt_surrendered) > 0, \'\', pr3.homeowner_ins_exp_countdown) homeowner_ins_exp_countdown, 
                           if (length(pr3.apt_surrendered) > 0, \'\', pr3.homeowner_ins_interval_remaining) homeowner_ins_interval_remaining,
                           \'\' no_email_for_apartment,
                           \'\' no_email_for_shareholders,
                           pr3.last_name, pr3.first_name, pr3.email_address, if(pr3.mds_resident_category = \'SHAREHOLDER\', \'\', \'No Designated Shareholder\') as shareholder_status, 
                           pr3.cell_phone, pr3.evening_phone, 
                            \'\' last_name2, \'\' first_name2, \'\' email_address2,
                           \'\' cell_phone2, \'\' evening_phone2     
                    from pennsouth_apt as pa
                     left join	 pennsouth_resident as pr3
                      ON
                        pa.apartment_id = pr3.pennsouth_apt_apartment_id
                      inner join pennsouth_bldg as b1
                      ON 
                        pa.building_id = b1.building_id
                      where  not exists
                    ( 
                    SELECT  \'x\'            
                      FROM pennsouth_resident as pr
                               JOIN pennsouth_bldg as b
                           ON
                               pr.building = b.building_id
                               JOIN mds_export as me
                            ON
                                pr.mds_export_id = me.mds_export_id
                               LEFT JOIN
                                pennsouth_resident as pr2
                           ON
                               pr.building = pr2.building
                           and pr.floor_number = pr2.floor_number
                           and pr.apt_line	= pr2.apt_line
                           and pr.pennsouth_resident_id < pr2.pennsouth_resident_id
                           and pr.mds_resident_category = pr2.mds_resident_category
                           and concat(pr.first_name, pr.last_name) <> concat(pr2.first_name, pr2.last_name) 
                              LEFT JOIN
                                  mds_export as me2
                              ON
                                  pr2.mds_export_id = me2.mds_export_id
                              LEFT JOIN
                           (
                           select  distinct apt.building_id, apt.floor_number, apt.apt_line, apt.apartment_name
                           from 
                                pennsouth_apt as apt
                                inner join pennsouth_resident as pr
                           where
                               apt.apartment_id = pr.Pennsouth_apt_apartment_id
                           and  not exists (
                           select  \'x\'
                           from pennsouth_resident pr2 
                            where pr.pennsouth_apt_apartment_id = pr2.pennsouth_apt_apartment_id and
                            pr2.email_address <>:noEmailAddress )
                            order by apt.building_id, apt.floor_number, apt.apt_line, apt.apartment_name
                           ) 
                           apts_no_email
                               ON
                               pr.building = apts_no_email.building_id
                           and pr.floor_number = apts_no_email.floor_number
                           and pr.apt_line		= apts_no_email.apt_line
                           WHERE
                               pr.mds_resident_category =:shareholderResidentCategory  
                            and pa.apartment_id = pr.pennsouth_apt_apartment_id)
        UNION
          SELECT distinct pr.building, b.address, concat( b.city, \', \', b.state, \' \', b.zip) city_state_zip, 
                                   pr.floor_number, pr.apt_line, pr.apt_surrendered, pr2.mds_resident_category mds_2nd_resident_category,
                                   pr.homeowner_ins_exp_date,                        
                                   if (length(pr.apt_surrendered) > 0, \'\', pr.homeowner_ins_exp_countdown) homeowner_ins_exp_countdown, 
                                   if (length(pr.apt_surrendered) > 0, \'\', pr.homeowner_ins_interval_remaining) homeowner_ins_interval_remaining,
                                   if(apts_no_email.building_id is not null, \'No Email for Apartment\', \'\') no_email_for_apartment,
                                   if( length(trim(pr.email_address)) = 0 and (length(trim(pr2.email_address)) = 0 or pr2.email_address is null), \'No Email for Shareholders\', \'\') no_email_for_shareholders,
                                   pr.last_name, pr.first_name, me.email_address, if(pr.mds_resident_category = \'SHAREHOLDER\', \'\', \'No Designated Shareholder\') as shareholder_status, 
                                   pr.cell_phone, pr.evening_phone, 
                                   pr2.last_name last_name2, pr2.first_name first_name2, me2.email_address email_address2,
                                   pr2.cell_phone cell_phone2, pr2.evening_phone evening_phone2                                
                               FROM pennsouth_resident as pr
                                   JOIN pennsouth_bldg as b
                               ON
                                   pr.building = b.building_id
                                   JOIN mds_export as me
                                ON
                                    pr.mds_export_id = me.mds_export_id
                                   LEFT JOIN
                                    pennsouth_resident as pr2
                               ON
                                   pr.building = pr2.building
                               and pr.floor_number = pr2.floor_number
                               and pr.apt_line	= pr2.apt_line
                               and pr.pennsouth_resident_id < pr2.pennsouth_resident_id
                               and pr.mds_resident_category = pr2.mds_resident_category
                               and concat(pr.first_name, pr.last_name) <> concat(pr2.first_name, pr2.last_name) 
                                  LEFT JOIN
                                      mds_export as me2
                                  ON
                                      pr2.mds_export_id = me2.mds_export_id
                                  LEFT JOIN
                               (
                               select  distinct apt.building_id, apt.floor_number, apt.apt_line, apt.apartment_name
                               from 
                                    pennsouth_apt as apt
                                    inner join pennsouth_resident as pr
                               where
                                   apt.apartment_id = pr.Pennsouth_apt_apartment_id
                               and  not exists (
                               select  \'x\'
                               from pennsouth_resident pr2 
                                where pr.pennsouth_apt_apartment_id = pr2.pennsouth_apt_apartment_id and
                                pr2.email_address <> :noEmailAddress)
                                order by apt.building_id, apt.floor_number, apt.apt_line, apt.apartment_name
                               ) 
                               apts_no_email
                                   ON
                                   pr.building = apts_no_email.building_id
                               and pr.floor_number = apts_no_email.floor_number
                               and pr.apt_line		= apts_no_email.apt_line
                               WHERE
                                   pr.mds_resident_category =:shareholderResidentCategory                         
                               order by 1, 4, 5, 7 desc'; // building / floor_num / apt line / 2nd resident_category



                $statement = $this->getEntityManager()->getConnection()->prepare($query);
                // Set parameters
                $statement->bindValue( 'noEmailAddress', '');
                $statement->bindValue( 'shareholderResidentCategory', 'SHAREHOLDER');

                $statement->execute();

                $homeownersInsuranceReportRows = $statement->fetchAll();

                return $homeownersInsuranceReportRows;
        }
        catch (\Exception $exception) {
            print("\n" . "Fatal Exception occurred in ManagementReportsWriter->getHomeownersInsuranceReportRows! ");
            print ("\n Exception->getMessage() : " . $exception->getMessage());
            print "Type: " . $exception->getCode(). "\n";
            print("\n" . "Exiting from program.");
            throw $exception;
        }

    }


    /**
     *       1/6/2020 - SQL modified to include ALL pennsouth apartments - UNION of 2 selects
     *          - 1) returns apts where there is no designated shareholder
     *          - 2) returns apts having a designated shareholder (the vast majority fall in this category)
     * @return array
     * @throws \Exception
     */
    private function getIncomeAffidavitReportRows() {

        try {

            $query =
                        'SELECT  distinct pr3.building, b1.address, concat( b1.city, \', \', b1.state, \' \', b1.zip) city_state_zip, 
                              pr3.floor_number, pr3.apt_line, pr3.apt_surrendered, 
							\'\' no_email_for_apartment,
                           if( length(trim(pr3.email_address)) = 0, \'No Email for Shareholders\', \'\') no_email_for_shareholders,
                           pr3.last_name, pr3.first_name, 
                           \'\' mds_export_email_address, pr3.cell_phone, pr3.evening_phone, 
                           if(pr3.mds_resident_category = \'SHAREHOLDER\', \'\', \'No Designated Shareholder\') as shareholder_status, 
                               \' \' last_name2, \' \' first_name2, \' \' email_address2,
                                \' \'  cell_phone2, \' \' evening_phone2,
                               \'\' inc_affidavit_receipt_date, \'\' inc_affidavit_received,
                                \'\' inc_affidavit_date_discrepancy, \'\' first_annual_deadline,
                                \'\' second_annual_deadline, \'\' late_charge1, 
                                \'\' late_charge2, \'\' move_in_date                               
                        from pennsouth_apt as pa
                         left join	 pennsouth_resident as pr3
                          ON
                            pa.apartment_id = pr3.pennsouth_apt_apartment_id
                          inner join pennsouth_bldg as b1
                          ON 
                            pa.building_id = b1.building_id
                          where  not exists
                          (
                           SELECT  \'X\'        
                                 FROM 
							income_affidavit as ia
                            JOIN
							pennsouth_resident as pr
                            ON ia.income_affidavit_id = 1
                           JOIN pennsouth_bldg as b
                       ON
                           pr.building = b.building_id
                           JOIN mds_export as me
						ON
							pr.mds_export_id = me.mds_export_id
                           LEFT JOIN
                            pennsouth_resident as pr2
                       ON
                           pr.building = pr2.building
                       and pr.floor_number = pr2.floor_number
                       and pr.apt_line	= pr2.apt_line
                       and pr.pennsouth_resident_id < pr2.pennsouth_resident_id
                       and pr.mds_resident_category = pr2.mds_resident_category
                       and concat(pr.first_name, pr.last_name) <> concat(pr2.first_name, pr2.last_name)
                           LEFT JOIN
                           mds_export as me2
						ON
                           pr2.mds_export_id = me2.mds_export_id
                           LEFT JOIN
                       missing_email_apt apts_no_email
                           ON
                           pr.building = apts_no_email.building_id
                       and pr.floor_number = apts_no_email.floor_number
                       and pr.apt_line		= apts_no_email.apt_line
                       WHERE
                           pr.mds_resident_category =:residentCategory
                                  and  pa.apartment_id = pr.Pennsouth_Apt_apartment_id     
  )     
            UNION
                SELECT distinct pr.building, b.address, concat( b.city, \', \', b.state, \' \', b.zip) city_state_zip, 
                           pr.floor_number, pr.apt_line, 
                           pr.apt_surrendered, 
                           if(apts_no_email.building_id is not null, \'No Email for Apartment\', \'\') no_email_for_apartment,
                           if( length(trim(pr.email_address)) = 0 and (length(trim(pr2.email_address)) = 0 or pr2.email_address is null), \'No Email for Shareholders\', \'\') no_email_for_shareholders,
                           pr.last_name, pr.first_name, 
                           me.email_address mds_export_email_address, pr.cell_phone, pr.evening_phone, 
                           if(pr.mds_resident_category = \'SHAREHOLDER\', \'\', \'No Designated Shareholder\') as shareholder_status, 
                           if(pr2.last_name is null, \'\', pr2.last_name) last_name2, if(pr2.first_name is null, \'\', pr2.first_name) first_name2, 
                           if(me2.email_address is null, \'\', me2.email_address) mds_export_email_address2,
                           if(pr2.cell_phone is null, \'\', pr2.cell_phone) cell_phone2, if(pr2.evening_phone is null, \'\', pr2.evening_phone) evening_phone2,
                           date_format(pr.inc_affidavit_receipt_date, \'%m/%d/%Y\') inc_affidavit_receipt_date, pr.inc_affidavit_received, pr.inc_affidavit_date_discrepancy,
                           ( CASE 
                            WHEN DAYOFWEEK(ia.first_annual_deadline) != 1 and DAYOFWEEK(ia.first_annual_deadline) != 7 THEN ia.first_annual_deadline
                            WHEN DAYOFWEEK(ia.first_annual_deadline) = 1 THEN DATE_ADD(ia.first_annual_deadline, INTERVAL 1 DAY)
                            WHEN DAYOFWEEK(ia.first_annual_deadline) = 7 THEN DATE_ADD(ia.first_annual_deadline, INTERVAL 2 DAY)
                            ELSE ia.first_annual_deadline                                 
                          END) first_annual_deadline,
                          ( CASE 
                            WHEN DAYOFWEEK(ia.second_annual_deadline) != 1 and DAYOFWEEK(ia.second_annual_deadline) != 7 THEN ia.second_annual_deadline
                            WHEN DAYOFWEEK(ia.second_annual_deadline) = 1 THEN DATE_ADD(ia.second_annual_deadline, INTERVAL 1 DAY)
                            WHEN DAYOFWEEK(ia.second_annual_deadline) = 7 THEN DATE_ADD(ia.second_annual_deadline, INTERVAL 2 DAY)
                            ELSE ia.second_annual_deadline
                          END) second_annual_deadline,
                           ( CASE 
								WHEN (CURDATE() > ia.first_annual_deadline and pr.inc_affidavit_receipt_date is not null 
									and DATE_FORMAT(pr.inc_affidavit_receipt_date, "%Y") < DATE_FORMAT(CURDATE(), "%Y" )) THEN \'Invalid Receipt Date\' 
								WHEN DAYOFWEEK(ia.first_annual_deadline) != 1 and DAYOFWEEK(ia.first_annual_deadline) != 7 THEN
									if(CURDATE() > ia.first_annual_deadline 
                                    and (pr.inc_affidavit_receipt_date is null or pr.inc_affidavit_receipt_date > ia.first_annual_deadline ), ia.first_deadline_late_charge, \'\')
								WHEN DAYOFWEEK(ia.first_annual_deadline) = 1 THEN 
									IF(CURDATE() > DATE_ADD(ia.first_annual_deadline, INTERVAL 1 DAY) 
                                    and (pr.inc_affidavit_receipt_date is null or pr.inc_affidavit_receipt_date > DATE_ADD(ia.first_annual_deadline, INTERVAL 1 DAY) ), ia.first_deadline_late_charge, \'\' )
								WHEN DAYOFWEEK(ia.first_annual_deadline) = 7 THEN 
									IF(CURDATE() > DATE_ADD(ia.first_annual_deadline, INTERVAL 1 DAY) 
                                    and (pr.inc_affidavit_receipt_date is null or pr.inc_affidavit_receipt_date > DATE_ADD(ia.first_annual_deadline, INTERVAL 1 DAY) ), ia.first_deadline_late_charge, \'\' )
                                ELSE \'\'
                                    
                           END) LATE_CHARGE1,
						  ( CASE 
								WHEN (CURDATE() > ia.second_annual_deadline and pr.inc_affidavit_receipt_date is not null 
									and DATE_FORMAT(pr.inc_affidavit_receipt_date, "%Y") < DATE_FORMAT(CURDATE(), "%Y" )) THEN \'Invalid Receipt Date\' 
								WHEN DAYOFWEEK(ia.second_annual_deadline) != 1 and DAYOFWEEK(ia.second_annual_deadline) != 7 THEN
									if(CURDATE() > ia.second_annual_deadline 
                                    and (pr.inc_affidavit_receipt_date is null or pr.inc_affidavit_receipt_date > ia.second_annual_deadline ), ia.second_deadline_late_charge, \'\')
								WHEN DAYOFWEEK(ia.second_annual_deadline) = 1 THEN 
									IF(CURDATE() > DATE_ADD(ia.second_annual_deadline, INTERVAL 1 DAY) 
                                    and (pr.inc_affidavit_receipt_date is null or pr.inc_affidavit_receipt_date > DATE_ADD(ia.second_annual_deadline, INTERVAL 1 DAY) ), ia.second_deadline_late_charge, \'\' )
								WHEN DAYOFWEEK(ia.first_annual_deadline) = 7 THEN 
									IF(CURDATE() > DATE_ADD(ia.second_annual_deadline, INTERVAL 1 DAY) 
                                    and (pr.inc_affidavit_receipt_date is null or pr.inc_affidavit_receipt_date > DATE_ADD(ia.second_annual_deadline, INTERVAL 1 DAY) ), ia.second_deadline_late_charge, \'\' )
								ELSE \'\'
                                    
                           END) LATE_CHARGE2,
                          date_format(pr.move_in_date, \'%m/%d/%Y\') move_in_date
                       FROM 
							income_affidavit as ia
                            JOIN
							pennsouth_resident as pr
                            ON ia.income_affidavit_id = 1
                           JOIN pennsouth_bldg as b
                       ON
                           pr.building = b.building_id
                           JOIN mds_export as me
						ON
							pr.mds_export_id = me.mds_export_id
                           LEFT JOIN
                            pennsouth_resident as pr2
                       ON
                           pr.building = pr2.building
                       and pr.floor_number = pr2.floor_number
                       and pr.apt_line	= pr2.apt_line
                       and pr.pennsouth_resident_id < pr2.pennsouth_resident_id
                       and pr.mds_resident_category = pr2.mds_resident_category
                       and concat(pr.first_name, pr.last_name) <> concat(pr2.first_name, pr2.last_name)
                           LEFT JOIN
                           mds_export as me2
						ON
                           pr2.mds_export_id = me2.mds_export_id
                           LEFT JOIN
                       missing_email_apt apts_no_email
                           ON
                           pr.building = apts_no_email.building_id
                       and pr.floor_number = apts_no_email.floor_number
                       and pr.apt_line		= apts_no_email.apt_line
                       WHERE
                           pr.mds_resident_category =:residentCategory
                       order by 1, 4, 5 ASC, 14 desc'; // building_id, floor_number, apt_line asc, mds_resident_category desc



                $statement = $this->getEntityManager()->getConnection()->prepare($query);
                // Set parameters
                $statement->bindValue( 'residentCategory', 'SHAREHOLDER');

                $statement->execute();

                $incomeAffidavitReportRows = $statement->fetchAll();

                return $incomeAffidavitReportRows;
        }
        catch (\Exception $exception) {
            print("\n" . "Fatal Exception occurred in ManagementReportsWriter->getIncomeAffidavitReportRows! ");
            print ("\n Exception->getMessage() : " . $exception->getMessage());
            print "Type: " . $exception->getCode(). "\n";
            print("\n" . "Exiting from program.");
            throw $exception;
        }

    }

    /*
     * -- 1. Find all instances where shareholder has been designated in the shareholder_flag column (tenant1/tenant2 fields in MDS)
     * --      and the mds_resident_category is not designated 'SHAREHOLDER
     * -- 2. Find all instances where there is no value in the mds_resident_category column
     * -- 3. Find all instances where there are multiple values of resident_category assigned as determined by there being more than one category with the field delimiter of '+'
     */
    private function getMdsDataEntryGapsReportRows() {

        try {

            $query =
                      'select distinct pr.building, pr.floor_number, pr.apt_line, pr.last_name, pr.first_name, pr.daytime_phone, pr.evening_phone,
                          pr.cell_phone, me.email_address,
                            pr.mds_resident_category, pr.shareholder_flag, me.category mds_export_category, pr.apt_surrendered
                        from pennsouth_resident pr
                          JOIN mds_export me
                          ON pr.mds_export_id = me.mds_export_id
                        where pr.shareholder_flag =:residentCategoryPlus
                        AND pr.mds_resident_category !=:residentCategory
                        UNION
                        select distinct pr.building, pr.floor_number, pr.apt_line, pr.last_name, pr.first_name, pr.daytime_phone, pr.evening_phone,
                          pr.cell_phone, me.email_address,
                            pr.mds_resident_category, pr.shareholder_flag, me.category mds_export_category, pr.apt_surrendered
                        from pennsouth_resident pr
                          JOIN mds_export me
                          ON pr.mds_export_id = me.mds_export_id
                        where 
                         pr.mds_resident_category =:blankResidentCategory
                        UNION
                        select distinct pr.building, pr.floor_number, pr.apt_line, pr.last_name, pr.first_name, pr.daytime_phone, pr.evening_phone,
                          pr.cell_phone, me.email_address,
                            pr.mds_resident_category, pr.shareholder_flag, me.category mds_export_category, pr.apt_surrendered
                        from pennsouth_resident pr
                          JOIN mds_export me
                          ON pr.mds_export_id = me.mds_export_id
                        where 
                         length(me.category) - length(replace(me.category, \'+\', \'\')) > 1
                        order by 1, 2, 3, 4, 5  ';



                $statement = $this->getEntityManager()->getConnection()->prepare($query);
                // Set parameters
                $statement->bindValue( 'residentCategoryPlus', 'SHAREHOLDER+');
                $statement->bindValue( 'residentCategory', 'SHAREHOLDER');
                $statement->bindValue( 'blankResidentCategory', '');

                $statement->execute();

                $mdsDataEntryGapsReportRows = $statement->fetchAll();

                return $mdsDataEntryGapsReportRows;


        }
        catch (\Exception $exception) {
            print("\n" . "Fatal Exception occurred in ManagementReportsWriter->getMdsDataEntryGapsReportRows! ");
            print ("\n Exception->getMessage() : " . $exception->getMessage());
            print "Type: " . $exception->getCode(). "\n";
            print("\n" . "Exiting from program.");
            throw $exception;
        }

    }

    private function getPennsouthResidentReportRows() {

           try {

               $query =
                         'SELECT pr.pennsouth_resident_id, pr.pennsouth_apt_apartment_id, pr.building, 
                               pr.floor_number, pr.apt_line, 
                               concat(pr.building, \'-\', pr.floor_number, pr.apt_line) bldg_fl_apt,
                               pr.last_name, pr.first_name, pr.email_address,
                               pr.mds_resident_category, pr.daytime_phone, pr.evening_phone, pr.cell_phone,
                               pr.person_id, pr.toddler_room_member, pr.youth_room_member, pr.ceramics_member,
                               pr.woodworking_member, pr.gym_member, pr.garden_member, pr.decal_num, 
                               pr.parking_lot_location, pr.vehicle_reg_exp_date, pr.vehicle_reg_exp_countdown,
                               pr.vehicle_reg_interval_remaining, pr.vehicle_model, pr.vehicle_license_plate_num,
                               pr.homeowner_ins_exp_date, pr.homeowner_ins_exp_countdown, pr.homeowner_ins_interval_remaining,
                               pr.birth_date, pr.move_in_date, pr.shareholder_flag, pr.inc_affidavit_receipt_date, 
                               pr.inc_affidavit_received, pr.inc_affidavit_date_discrepancy, pr.storage_locker_closet_bldg_num,
                               pr.storage_locker_num, pr.storage_closet_floor_num, pr.dog_tag_num, pr.is_dog_in_apt,
                               pr.bike_rack_location, pr.bike_rack_bldg, pr.bike_rack_room, pr.apt_surrendered, pr.mds_export_id,
                               pr.hperson_id, pr.last_changed_date
                           FROM pennsouth_resident pr
                           where length(pr.email_address) > 0
                             and pr.mds_resident_category =:residentCategory
                        order by pr.pennsouth_apt_apartment_id, pr.last_name, pr.first_name';



                   $statement = $this->getEntityManager()->getConnection()->prepare($query);
                   $statement->bindValue( 'residentCategory', 'SHAREHOLDER');


                   $statement->execute();

                   $pennsouthResidentReportRows = $statement->fetchAll();

                   return $pennsouthResidentReportRows;


           }
           catch (\Exception $exception) {
               print("\n" . "Fatal Exception occurred in ManagementReportsWriter->getPennsouthResidentReportRows! ");
               print ("\n Exception->getMessage() : " . $exception->getMessage());
               print "Type: " . $exception->getCode(). "\n";
               print("\n" . "Exiting from program.");
               throw $exception;
           }

       }



}