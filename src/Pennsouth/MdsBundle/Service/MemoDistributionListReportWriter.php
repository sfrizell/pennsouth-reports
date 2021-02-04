<?php
/**
 * Created by PhpStorm.
 * User: sfrizell
 * Date: 2/2/21
 * Time: 6:32 PM
 */

namespace Pennsouth\MdsBundle\Service;

use Doctrine\ORM\EntityManager;
use PHPExcel_Style_Fill;
use PHPExcel_Cell;
use PHPExcel;

class MemoDistributionListReportWriter
    /**
     * The MemoDistributionListReportWriter is essentially a clone of the AptsWithNoShareholderHavingEmailReportWriter, modified for use by the
     * porters as follows:
     *  1) Exclude any rows where apt_surrendered is not blank, i.e., do not include any rows for apartments that have been surrendered.
     *  2) Include in the report only the Apartment_name column from the pennsouth_apt table
     * Note: The SQL query contains more columns than just the apartment name so that the same logic can be used as in the
     *       AptsWithNoShareholderHavingEmailReportWriter to create page breaks on building-id in the generated spreadsheet
     */
{
    const MEMO_DISTRIBUTION_LIST_FILE_NAME                     = 'memo_distribution_list.xlsx';

    const MEMO_DISTRIBUTION_LIST_HEADER_ARRAY = array(
        'Apt Name'
    );

    const WORKING_MEMO_DISTRIBUTION_LIST_HEADER_ARRAY = array(
        'Building',
        'Floor Number',
        'Apt Line',
        'Apt Name'
    );


    const MEMO_DISTRIBUTION_LIST_COL_NAMES = array(
        'building_id',
        'floor_number',
        'apt_line',
        'apartment_name'
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
    public function createSpreadsheetMemoDistributionList() {

        $apartmentsWithNoEmailAddressesNotSurrendered = $this->getMemoDistributionListQueryDb();

        $title              = 'Memo Distribution List';
        $description        = 'List of Pennsouth apartments where no shareholder has provided an email address and apt not surrendered.';
        $category           = 'List Management Reports';
        $spreadsheetTabName = 'Memo Distribution List';

        $phpExcelObject = $this->getPhpExcelObjectAndSetHeadings(self::MEMO_DISTRIBUTION_LIST_HEADER_ARRAY, $title, $description, $category);



        if (!is_null($phpExcelObject) and $phpExcelObject instanceof \PHPExcel) {

            $fileWriteCtr = 0;


            $rowCtr = 1;
            $breakRow = 1;

            $colLimit = count(self::MEMO_DISTRIBUTION_LIST_COL_NAMES);
            $phpExcelObject->setActiveSheetIndex(0);
            $prevBuildingId = "";
            $prevAptName = "";
            foreach ($apartmentsWithNoEmailAddressesNotSurrendered as $apartmentWithNoEmailAddressRow) {
                $rowCtr++;

                if ($prevBuildingId !== "" and $prevBuildingId !== $apartmentWithNoEmailAddressRow[self::MEMO_DISTRIBUTION_LIST_COL_NAMES[0]]) {
                    if ($rowCtr > 2) {
                        $breakRow = $rowCtr - 1;
                    }
                    else {
                        $breakRow = $rowCtr;
                    }
                    $phpExcelObject->getActiveSheet()->setBreak('A' . $breakRow, \PHPExcel_Worksheet::BREAK_ROW);
                }
                if ( $prevAptName !== $apartmentWithNoEmailAddressRow[self::MEMO_DISTRIBUTION_LIST_COL_NAMES[3]]) {
                    // just writing one cell per row - the value of apt_name
                    $apt_name_value = 3;
                    $currentLetter = PHPExcel_Cell::stringFromColumnIndex(0);
                    $cellId = $currentLetter . $rowCtr;
                    $phpExcelObject->getActiveSheet()
                        ->setCellValue($cellId, $apartmentWithNoEmailAddressRow[self::MEMO_DISTRIBUTION_LIST_COL_NAMES[$apt_name_value]]);

                }
                else { // so that blank line isn't written...
                    $rowCtr = $rowCtr - 1;
                }
                $prevBuildingId =  $apartmentWithNoEmailAddressRow[self::MEMO_DISTRIBUTION_LIST_COL_NAMES[0]];
                $prevAptName    =  $apartmentWithNoEmailAddressRow[self::MEMO_DISTRIBUTION_LIST_COL_NAMES[3]];

                // }



            }

            $phpExcelObject->getActiveSheet()->setTitle($spreadsheetTabName);
            // Set active sheet index to the first sheet, so Excel opens this as the first sheet
            $phpExcelObject->setActiveSheetIndex(0);

            // create the writer
            $writer = $this->phpExcel->createWriter($phpExcelObject, 'Excel2007');
            // The save method is documented in the official PHPExcel library
            $fileWriteCtr++;
            $writer->save($this->appOutputDir . '/' . self::MEMO_DISTRIBUTION_LIST_FILE_NAME);


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

            $apt_name_index = 3;
            //foreach (range(0, $totalCols) as $col) {
            foreach (range($apt_name_index, $totalCols) as $col) {
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
    private function getMemoDistributionListQueryDb() {

        try {

            // $query = $this->getEntityManager()->createNativeQuery
            $query =
                'select distinct apt.building_id, apt.floor_number, apt.apt_line, apt.apartment_name
                                    from 
                                         pennsouth_apt as apt
                                         inner join pennsouth_resident as pr
                                      on 
                                        apt.apartment_id = pr.Pennsouth_apt_apartment_id
                                        inner join mds_export me
                                      on 
                                        pr.mds_export_id = me.mds_export_id
                                    where 
                                     pr.apt_surrendered = "" 
                                    and  not exists (
                                    select  \'x\'
                                    from pennsouth_resident pr2
                                     where pr.pennsouth_apt_apartment_id = pr2.pennsouth_apt_apartment_id and
                                     pr2.email_address <> :emailAddress1
                                     and pr2.mds_resident_category = :mdsResidentCategory)
                   UNION
                select distinct apt.building_id, apt.floor_number, apt.apt_line, apt.apartment_name
                  from 
                       pennsouth_apt as apt
                       inner join pennsouth_resident as pr
                  on
                      apt.apartment_id = pr.Pennsouth_apt_apartment_id
                  join mds_export me
				  on 
						pr.mds_export_id = me.mds_export_id
                  where me.status_codes like :statusCodes
                       and pr.apt_surrendered = ""                
                   order by 1, 2, 3, 4 asc' ;



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
            print("\n" . "Fatal Exception occurred in MemoDistributionListReportWriter->getMemoDistributionListQueryDb! ");
            print ("\n Exception->getMessage() : " . $exception->getMessage());
            print "Type: " . $exception->getCode(). "\n";
            print("\n" . "Exiting from program.");
            throw $exception;
        }

    }

}