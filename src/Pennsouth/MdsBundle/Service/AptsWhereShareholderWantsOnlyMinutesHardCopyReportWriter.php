<?php
/**
 * AptsWhereShareholderWantsOnlyMinutesHardCopyReportWriter.php
 * User: sfrizell
 * Date: 12/4/18
 *  Function: Generate report of Pennsouth apartments where shareholder only wants hard copy of Minutes - everything else by email.
 *   The report also includes all those apartments where no shareholder in the apt has an email address.
 */

namespace Pennsouth\MdsBundle\Service;

use Doctrine\ORM\EntityManager;
use PHPExcel_Style_Fill;
use PHPExcel_Style_Color;
use PHPExcel_Cell;
use Liuggio\ExcelBundle\Factory;
use PHPExcel_CachedObjectStorageFactory;
use PHPExcel_Settings;
use Doctrine\ORM\Query;
use PHPExcel;

class AptsWhereShareholderWantsOnlyMinutesHardCopyReportWriter
{
    const LIST_APTS_WHERE_SHAREHOLDER_WANTS_MINUTES_HARD_COPY_FILE_NAME                     = 'printed_board_minutes.xlsx';

    const APTS_WHERE_SHAREHOLDER_WANTS_MINUTES_HARD_COPY_HEADER_ARRAY = array(
               'Building',
               'Floor Number',
               'Apt Line',
               'Apt Name',
                'Apt Surrendered',
                'Email Address',
                'Hard Copy Preference'
       );

    const APTS_WHERE_SHAREHOLDER_WANTS_MINUTES_HARD_COPY_COL_NAMES = array(
                   'building_id',
                   'floor_number',
                   'apt_line',
                   'apartment_name',
                   'apt_surrendered',
                    'email_address',
                    'hard_copy_preference'
           );

    private $entityManager;
    private $phpExcel;
    private $appOutputDir;
    private $env;

     public function __construct (EntityManager $entityManager,  $phpExcel, $appOutputDir, $env ) {

         $this->entityManager    = $entityManager;
         $this->phpExcel         = $phpExcel;
         $this->appOutputDir     = $appOutputDir;
         $this->env             = $env;

     }

     public function getEntityManager() {
         return $this->entityManager;
     }


    /**
     * NOTE: Set page break whenever there is a break on buildingId.
     *  New code added 3/17/19 to eliminate any instances opf multiple rows per apartment (when there are shareholders in an apartment with more than 1 email address or a shareholder with email address(s) and
     *   another shareholder without email address.
     * @return bool
     */
    public function createSpreadsheetAptsShareholderWantsMinutesHardCopy() {

        $apartmentsWithNoEmailAddresses = $this->getApartmentsWhereShareholderWantsMinutesHardCopyQueryDb();

        $title              = 'Apts where Shareholder Wants Minutes Hard Copy';
       $description        = 'List of Pennsouth apartments where shareholder wants Minutes hard copy.';
       $category           = 'List Management Reports';
       $spreadsheetTabName = 'Apts Shareholder Minutes HC';

       $phpExcelObject = $this->getPhpExcelObjectAndSetHeadings(self::APTS_WHERE_SHAREHOLDER_WANTS_MINUTES_HARD_COPY_HEADER_ARRAY, $title, $description, $category);



            if (!is_null($phpExcelObject) and $phpExcelObject instanceof \PHPExcel) {

                $fileWriteCtr = 0;


                $rowCtr = 1;
                $breakRow = 1;

                $colLimit = count(self::APTS_WHERE_SHAREHOLDER_WANTS_MINUTES_HARD_COPY_COL_NAMES);
                $phpExcelObject->setActiveSheetIndex(0);
                $prevBuildingId = "";
                $prevAptName = "";
                foreach ($apartmentsWithNoEmailAddresses as $apartmentWithNoEmailAddressRow) {
                    $rowCtr++;


                    if ($prevBuildingId !== "" and $prevBuildingId !== $apartmentWithNoEmailAddressRow[self::APTS_WHERE_SHAREHOLDER_WANTS_MINUTES_HARD_COPY_COL_NAMES[0]]) {
                        if ($rowCtr > 2) {
                            $breakRow = $rowCtr - 1;
                        }
                        else {
                            $breakRow = $rowCtr;
                        }
                        $phpExcelObject->getActiveSheet()->setBreak('A' . $breakRow, \PHPExcel_Worksheet::BREAK_ROW);
                    }

                    if ( $prevAptName !== $apartmentWithNoEmailAddressRow[self::APTS_WHERE_SHAREHOLDER_WANTS_MINUTES_HARD_COPY_COL_NAMES[3]]) {
                        for ($i = 0; $i < $colLimit; $i++) {
                            $currentLetter = PHPExcel_Cell::stringFromColumnIndex($i);
                            $cellId = $currentLetter . $rowCtr;
                            //  print ("\$cellId: " . $cellId . "\n");
                            // if (!$key == 'Last Changed Date') {
                            $phpExcelObject->getActiveSheet()
                                ->setCellValue($cellId, $apartmentWithNoEmailAddressRow[self::APTS_WHERE_SHAREHOLDER_WANTS_MINUTES_HARD_COPY_COL_NAMES[$i]]);
                            // }
                        }
                    }
                    else { // so that blank line isn't written...
                        $rowCtr = $rowCtr - 1;
                    }
                     $prevBuildingId =  $apartmentWithNoEmailAddressRow[self::APTS_WHERE_SHAREHOLDER_WANTS_MINUTES_HARD_COPY_COL_NAMES[0]];
                    $prevAptName    =  $apartmentWithNoEmailAddressRow[self::APTS_WHERE_SHAREHOLDER_WANTS_MINUTES_HARD_COPY_COL_NAMES[3]];

                // }



                }

                $phpExcelObject->getActiveSheet()->setTitle($spreadsheetTabName);
                // Set active sheet index to the first sheet, so Excel opens this as the first sheet
                $phpExcelObject->setActiveSheetIndex(0);

                // create the writer
                $writer = $this->phpExcel->createWriter($phpExcelObject, 'Excel2007');
                // The save method is documented in the official PHPExcel library
                $fileWriteCtr++;
                $writer->save($this->appOutputDir . '/' . self::LIST_APTS_WHERE_SHAREHOLDER_WANTS_MINUTES_HARD_COPY_FILE_NAME);


                return TRUE;
            }

        return FALSE;

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
     * This function avoids the use of Doctrine's DQL and just uses raw SQL.
     *
     *  The status code of ']' means the shareholder only wants to receive hard copies of
     *   the Coop's Minutes and nothing else.
     *  The status code of '>' means the shareholder wants to receive hard copy of all communications
     * @return mixed
     * @throws \Exception
     */
    private function getApartmentsWhereShareholderWantsMinutesHardCopyQueryDb() {

          try {

              // $query = $this->getEntityManager()->createNativeQuery

              $query =
                  'select distinct apt.building_id, apt.floor_number, apt.apt_line, apt.apartment_name, pr.apt_surrendered,
                              \'\' as email_address,
                              (CASE 
                                WHEN (INSTR(me.status_codes, \']\') > 0) THEN \'Wants Minutes Hard Copy\'
								WHEN (INSTR(me.status_codes, \'>\') > 0) THEN \'Wants All Communications Hard Copy\' 
								WHEN (INSTR(me.status_codes, \']\') = 0) THEN \'\'
                              END) as \'hard_copy_preference\'
                                    from 
                                         pennsouth_apt as apt
                                         inner join pennsouth_resident as pr
                                      on 
                                        apt.apartment_id = pr.Pennsouth_apt_apartment_id
                                        inner join mds_export me
                                      on 
                                        pr.mds_export_id = me.mds_export_id
                                    and  not exists (
                                    select  \'x\'
                                    from pennsouth_resident pr2
                                     where pr.pennsouth_apt_apartment_id = pr2.pennsouth_apt_apartment_id and
                                      pr2.email_address <> :emailAddress1
                                     and pr2.mds_resident_category = :mdsResidentCategory)
                   UNION
                select distinct apt.building_id, apt.floor_number, apt.apt_line, apt.apartment_name, pr.apt_surrendered,
							if (pr.email_address <> \'\', \'Has Email Address\', \'\') as \'Email_Address\',
                               (CASE 
                                WHEN (INSTR(me.status_codes, \']\') > 0) THEN \'Wants Minutes Hard Copy\'
								WHEN (INSTR(me.status_codes, \'>\') > 0) THEN \'Wants All Communications Hard Copy\'
								WHEN (INSTR(me.status_codes, \']\') = 0) THEN \'\'
                              END) as \'hard_copy_preference\'
                  from 
                       pennsouth_apt as apt
                       inner join pennsouth_resident as pr
                  on
                      apt.apartment_id = pr.Pennsouth_apt_apartment_id
                  join mds_export me
				  on 
						pr.mds_export_id = me.mds_export_id
                  where me.status_codes like :MinutesStatusCodes                          
                   UNION
                select distinct apt.building_id, apt.floor_number, apt.apt_line, apt.apartment_name, pr.apt_surrendered,
							if (pr.email_address <> \'\', \'Has Email Address\', \'\') as \'Email_Address\',
                               (CASE
                                WHEN (INSTR(me.status_codes, \']\') > 0) THEN \'Wants Minutes Hard Copy\'
								WHEN (INSTR(me.status_codes, \'>\') > 0) THEN \'Wants All Communications Hard Copy\' 
								WHEN (INSTR(me.status_codes, \']\') = 0) THEN \'\'
                              END) as \'hard_copy_preference\'
                  from 
                       pennsouth_apt as apt
                       inner join pennsouth_resident as pr
                  on
                      apt.apartment_id = pr.Pennsouth_apt_apartment_id
                  join mds_export me
				  on 
						pr.mds_export_id = me.mds_export_id
                  where me.status_codes like :AllCommunicationStatusCodes                          
                   order by 1, 2, 3, 4 asc, 6 desc';



              $statement = $this->getEntityManager()->getConnection()->prepare($query);
                      // Set parameters
              $statement->bindValue( 'emailAddress1', '');
              $statement->bindValue( 'mdsResidentCategory', 'SHAREHOLDER');
              $statement->bindValue('MinutesStatusCodes', '%]%');
              $statement->bindValue('AllCommunicationStatusCodes', '%>%');
              $statement->execute();

              $apartmentsWithNoShareholderEmailAddresses = $statement->fetchAll();

              return $apartmentsWithNoShareholderEmailAddresses;

          }
          catch (\Exception $exception) {
              print("\n" . "Fatal Exception occurred in ApartmentsWhereShareholderWantsOnlyMinutesHardCopyReportWriter->getApartmentsWhereShareholderWantsMinutesHardCopyQueryDb! ");
              print ("\n Exception->getMessage() : " . $exception->getMessage());
              print "Type: " . $exception->getCode(). "\n";
              print("\n" . "Exiting from program.");
              throw $exception;
          }

      }

}