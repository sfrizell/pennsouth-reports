<?php
/**
 * AptsWithNoShareholderHavingEmailReportWriter.php
 * User: sfrizell
 * Date: 10/24/18
 *  Function: Generate report of Pennsouth apartments where no shareholder in the apt has an email address.
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

class AptsWithNoShareholderHavingEmailReportWriter
{
    const LIST_APTS_WITH_NO_SHAREHOLDER_EMAIL_ADDRESS_FILE_NAME                     = 'list_of_apts_with_no_shareholder_email.xlsx';

    const APTS_WITH_NO_SHAREHOLDER_EMAIL_ADDRESSES_HEADER_ARRAY = array(
               'Building',
               'Floor Number',
               'Apt Line',
               'Apt Name',
                'Apt Surrendered',
                'Email Address',
                'Hard Copy Preference'
       );

    const APTS_WITH_NO_SHAREHOLDER_EMAIL_ADDRESSES_COL_NAMES = array(
                   'building_id',
                   'floor_number',
                   'apt_line',
                   'apartment_name',
                   'apt_surrendered',
                    'email_address',
                    'Hard Copy Preference'
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
     * @return bool
     */
    public function createSpreadsheetAptsWithNoShareholderEmailAddresses() {

        $apartmentsWithNoEmailAddresses = $this->getApartmentsWithNoShareholderEmailAddressQueryDb();

        $title              = 'Apts with no Shareholder Email Address';
       $description        = 'List of Pennsouth apartments where no shareholder has provided an email address.';
       $category           = 'List Management Reports';
       $spreadsheetTabName = 'Apts With No Shareholder Email';

       $phpExcelObject = $this->getPhpExcelObjectAndSetHeadings(self::APTS_WITH_NO_SHAREHOLDER_EMAIL_ADDRESSES_HEADER_ARRAY, $title, $description, $category);



            if (!is_null($phpExcelObject) and $phpExcelObject instanceof \PHPExcel) {

                $fileWriteCtr = 0;


                $rowCtr = 1;
                $breakRow = 1;

                $colLimit = count(self::APTS_WITH_NO_SHAREHOLDER_EMAIL_ADDRESSES_COL_NAMES);
                $phpExcelObject->setActiveSheetIndex(0);
                $prevBuildingId = "";
                $prevAptName = "";
                foreach ($apartmentsWithNoEmailAddresses as $apartmentWithNoEmailAddressRow) {
                    $rowCtr++;

                    if ($prevBuildingId !== "" and $prevBuildingId !== $apartmentWithNoEmailAddressRow[self::APTS_WITH_NO_SHAREHOLDER_EMAIL_ADDRESSES_COL_NAMES[0]]) {
                        if ($rowCtr > 2) {
                            $breakRow = $rowCtr - 1;
                        }
                        else {
                            $breakRow = $rowCtr;
                        }
                        $phpExcelObject->getActiveSheet()->setBreak('A' . $breakRow, \PHPExcel_Worksheet::BREAK_ROW);
                    }
                    if ( $prevAptName !== $apartmentWithNoEmailAddressRow[self::APTS_WITH_NO_SHAREHOLDER_EMAIL_ADDRESSES_COL_NAMES[3]]) {
                        for ($i = 0; $i < $colLimit; $i++) {
                            $currentLetter = PHPExcel_Cell::stringFromColumnIndex($i);
                            $cellId = $currentLetter . $rowCtr;
                            //  print ("\$cellId: " . $cellId . "\n");
                            // if (!$key == 'Last Changed Date') {
                            $phpExcelObject->getActiveSheet()
                                ->setCellValue($cellId, $apartmentWithNoEmailAddressRow[self::APTS_WITH_NO_SHAREHOLDER_EMAIL_ADDRESSES_COL_NAMES[$i]]);
                            // }
                        }
                    }
                    else { // so that blank line isn't written...
                        $rowCtr = $rowCtr - 1;
                    }
                     $prevBuildingId =  $apartmentWithNoEmailAddressRow[self::APTS_WITH_NO_SHAREHOLDER_EMAIL_ADDRESSES_COL_NAMES[0]];
                    $prevAptName    =  $apartmentWithNoEmailAddressRow[self::APTS_WITH_NO_SHAREHOLDER_EMAIL_ADDRESSES_COL_NAMES[3]];

                // }



                }

                $phpExcelObject->getActiveSheet()->setTitle($spreadsheetTabName);
                // Set active sheet index to the first sheet, so Excel opens this as the first sheet
                $phpExcelObject->setActiveSheetIndex(0);

                // create the writer
                $writer = $this->phpExcel->createWriter($phpExcelObject, 'Excel2007');
                // The save method is documented in the official PHPExcel library
                $fileWriteCtr++;
                $writer->save($this->appOutputDir . '/' . self::LIST_APTS_WITH_NO_SHAREHOLDER_EMAIL_ADDRESS_FILE_NAME);


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
     * @return mixed
     * @throws \Exception
     */
    private function getApartmentsWithNoShareholderEmailAddressQueryDb() {

          try {

              // $query = $this->getEntityManager()->createNativeQuery
              $query =
                  'select distinct apt.building_id, apt.floor_number, apt.apt_line, apt.apartment_name, pr.apt_surrendered,
                              \'\' as email_address,
                              if(instr(me.status_codes,\'>\') = 0, \'\', \'Wants Hard Copy\' ) as \'Hard Copy Preference\'
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
                              if(instr(me.status_codes,\'>\') = 0, \'\', \'Wants Hard Copy\' ) as \'Hard Copy Preference\'
                  from 
                       pennsouth_apt as apt
                       inner join pennsouth_resident as pr
                  on
                      apt.apartment_id = pr.Pennsouth_apt_apartment_id
                  join mds_export me
				  on 
						pr.mds_export_id = me.mds_export_id
                  where me.status_codes like :statusCodes                     
                   order by 1, 2, 3, 4 asc, 6 desc' ;



              $statement = $this->getEntityManager()->getConnection()->prepare($query);
                      // Set parameters
              $statement->bindValue( 'emailAddress1', '');
              $statement->bindValue( 'mdsResidentCategory', 'SHAREHOLDER');
              $statement->bindValue('statusCodes', '%>%');
              $statement->execute();

              $apartmentsWithNoShareholderEmailAddresses = $statement->fetchAll();

              return $apartmentsWithNoShareholderEmailAddresses;

          }
          catch (\Exception $exception) {
              print("\n" . "Fatal Exception occurred in ApartmentsWithNoShareholderHavingEmailReportWriter->getApartmentsWithNoShareholderEmailAddressQueryDb! ");
              print ("\n Exception->getMessage() : " . $exception->getMessage());
              print "Type: " . $exception->getCode(). "\n";
              print("\n" . "Exiting from program.");
              throw $exception;
          }

      }

}