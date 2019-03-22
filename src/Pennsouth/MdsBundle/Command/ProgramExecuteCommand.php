<?php
/**
 * ProgramExecuteCommand.php
 * User: sfrizell
 * Date: 9/20/16
 *  Function: Command class to enable running the MDS to Aweber synchronization process from the command line.
 */

namespace Pennsouth\MdsBundle\Command;

use Pennsouth\MdsBundle\Entity\EmailNotifyParameters;
use Pennsouth\MdsBundle\Service\Emailer;
use Pennsouth\MdsBundle\Service\EmailNotifyParametersReader;
use Pennsouth\MdsBundle\Service\AptsWithNoResidentHavingEmailReportWriter;
use Pennsouth\MdsBundle\Service\AptsWithNoShareholderHavingEmailReportWriter;
use Pennsouth\MdsBundle\Service\AptsWhereShareholderWantsOnlyMinutesHardCopyReportWriter;
use Pennsouth\MdsBundle\Service\ManagementReportsWriter;
use Pennsouth\MdsBundle\Service\MdsChangeDetectionReportWriter;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Input\InputOption;

use Pennsouth\MdsBundle\Service\PennsouthResidentListReader;

use Symfony\Component\Debug\DebugClassLoader;

//DebugClassLoader::enable();

/**
 * Class ProgramExecuteCommand
 * @package Pennsouth\MdsBundle\Command
 * run this from the command line by issuing this command:
 *      php app/console app:pennsouth-reports
 */


class ProgramExecuteCommand extends ContainerAwareCommand {


    const DEFAULT_ADMINS                    =  array ( array("steve.frizell@gmail.com" => "Stephen Frizell"));
    const DEFAULT_ADMIN_EMAIL_RECIPIENT_ADDRESS       = 'steve.frizell@gmail.com';
    const DEFAULT_ADMIN_EMAIL_RECIPIENT_NAME          = 'Stephen Frizell';
    const REPORT_ON_APTS_WITH_NO_EMAIL                  = 'report-on-apts-where-no-resident-has-email-address';
    const REPORT_ON_APTS_WITH_NO_SHAREHOLDER_EMAIL      = 'report-on-apts-where-no-shareholder-has-email-address';
    const REPORT_ON_SHAREHOLDER_MINUTES_HARD_COPY      = 'report-on-apts-shareholder-wants-minutes-hard-copy';
    const PARKING_LOT_REPORT                            = 'parking-lot-report';
    const MDS_CHANGE_DETECTION_REPORT                   = 'MDS-change-detection-report';
    const HOMEOWNERS_INSURANCE_REPORT                   = 'homeowners-insurance-report';
    const INCOME_AFFIDAVIT_REPORT                       = 'income-affidavit-report';
    const MDS_DATA_ENTRY_GAPS_REPORT                    = 'mds-data-entry-gaps-report';
    const PENNSOUTH_SHAREHOLDERS_REPORT                 = 'pennsouth-shareholders-report';
    const APP_OUTPUT_DIRECTORY_DEV                      = "/app_output";
    const APP_OUTPUT_DIRECTORY_PROD                     = "/home/pennsouthdata/home/mgmtoffice/public_ftp";
    const ENVIRONMENT_DEV                               = 'dev';
    const ENVIRONMENT_PROD                              = 'prod';

    private $defaultEmailNotifyParameters;
    private $defaultEmailNotifyParametersArray = array();
   // private $adminEmailRecipients = array();
    private $adminEmailNotifyRecipients = array();
    private $runReportOnAptsWithNoEmail;
    private $runReportOnAptsWithNoShareholderEmail;
    private $runReportOnAptsShareholderWantsMinutesHardCopy;
    private $runParkingLotReport;
    private $runMdsChangeDetectionReport;
    private $runHomeownersInsuranceReport;
    private $runIncomeAffidavitReport;
    private $runMDsDataEntryGapsReport;
    private $runPennsouthShareholdersReport;
    private $emailNotifyReportOrProcessName = null;
    private $isExceptionRaised = FALSE;

    protected function configure() {
        $this
                // the name of the command (the part after "bin/console") <-- (sfrizell) this is syntax for symfony 3.0; version 2.8 should use: php app/console ...
                ->setName('app:pennsouth-reports')

                // the short description shown while running "php bin/console list"
                ->setDescription('Run Penn South reports.')

                // the full command description shown when running the command with
                // the "--help" option
                ->setHelp("This command runs Pennsouth reports based on input parameters to determine which reports to run..." . "\n"
                            . " The command has required arguments.")

                ->setDefinition(
                    new InputDefinition(array(
                        new InputOption(self::PARKING_LOT_REPORT, 'p', InputOption::VALUE_REQUIRED, 'Option to create Parking Lot Report: y/n', 'n'),
                        new InputOption(self::MDS_CHANGE_DETECTION_REPORT, 'm', InputOption::VALUE_REQUIRED, 'Option to create MDS Change Detection Report: y/n', 'n'),
                        new InputOption(self::HOMEOWNERS_INSURANCE_REPORT, 'i', InputOption::VALUE_REQUIRED, 'Option to create Homeowners Insurance Report: y/n', 'n'),
                        new InputOption(self::INCOME_AFFIDAVIT_REPORT, 'c', InputOption::VALUE_REQUIRED, 'Option to create Income Affidavit Report: y/n', 'n'),
                        new InputOption(self::MDS_DATA_ENTRY_GAPS_REPORT, 'd', InputOption::VALUE_REQUIRED, 'Option to create MDS Data Entry Discrepancies Report: y/n', 'n'),
                        new InputOption(self::PENNSOUTH_SHAREHOLDERS_REPORT, 'r', InputOption::VALUE_REQUIRED, 'Option to create Pennsouth Shareholders Report: y/n', 'n'),
                        new InputOption(self::REPORT_ON_APTS_WITH_NO_EMAIL, 'b', InputOption::VALUE_REQUIRED, 'Option to create spreadsheet listing apts where no resident has email address.: y/n', 'n'),
                        new InputOption(self::REPORT_ON_APTS_WITH_NO_SHAREHOLDER_EMAIL, 'a', InputOption::VALUE_REQUIRED, 'Option to create spreadsheet listing apts where no shareholder has email address.: y/n', 'n'),
                        new InputOption(self::REPORT_ON_SHAREHOLDER_MINUTES_HARD_COPY, 'f', InputOption::VALUE_REQUIRED, 'Option to create spreadsheet listing apts where shareholder wants Minutes hard copy.: y/n', 'n'),
                    ))
                )
            ;


    }

    public function getEntityManager() {
        return $this->getContainer()->get('doctrine')->getManager();
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        // outputs multiple lines to the console (adding "\n" at the end of each line)
            $output->writeln([
                'Invoking the process to read the MDS_Export table and use it to generate reports.',
                '============',
                '',
            ]);

        // just a little test
/*        $updateCtr = 0;
        $batchSize = 30;
        while ($updateCtr < 35)
        {
        	$updateCtr++;

        	$modulo = $updateCtr % $batchSize;
        	print("\n" . "\$updateCtr: " . $updateCtr . " \$modulo: " . $modulo );
        }

        exit(0);*/

        $runStartDate = new \DateTime("now");
        print("\n" . "Program run start date/time: " . $runStartDate->format('Y-m-d H:i:s') . "\n");

        print ("\n getenv('SYMFONY_ENV'): " . getenv('SYMFONY_ENV') . "\n");



        $rootDir = $this->getContainer()->getParameter('kernel.root_dir');

        $rootDir = rtrim($rootDir, "/app");

        // get environment -- 'prod' or 'dev' -- on production server, this environment variable ( 'SYMFONY_ENV') is set in root's .bash_profile
        //  when it is not explicitly set, it will get set to the default value of 'dev'
        // print ( "\n \$this->getContainer()->get('kernel')->getEnvironment(): " . $this->getContainer()->get('kernel')->getEnvironment() . "\n" );

        $env = $this->getContainer()->get('kernel')->getEnvironment();

        print("\n   environment: " . $env . "\n");

        if ($env == self::ENVIRONMENT_PROD) {
            $appOutputDir = self::APP_OUTPUT_DIRECTORY_PROD;
        }
        else {
            $appOutputDir = $rootDir . self::APP_OUTPUT_DIRECTORY_DEV;
        }

        print("\n \$rootDir: " . $rootDir . "\n");

        print("\n \$appOutputDir: " . $appOutputDir . "\n");

        print ("\n current_user: " . get_current_user() . "\n");


        // default is FALSE, so anything other than parameter of 'y' is interpreted as FALSE...
        $this->runParkingLotReport = ( is_null( $input->getOption(self::PARKING_LOT_REPORT)) ? FALSE
                                        : ( strtolower($input->getOption(self::PARKING_LOT_REPORT)) == 'y' ? TRUE : FALSE ) );

        // default is FALSE, so anything other than parameter of 'y' is interpreted as FALSE...
        $this->runMdsChangeDetectionReport = ( is_null( $input->getOption(self::MDS_CHANGE_DETECTION_REPORT)) ? FALSE
                                        : ( strtolower($input->getOption(self::MDS_CHANGE_DETECTION_REPORT)) == 'y' ? TRUE : FALSE ) );

        // default is FALSE, so anything other than parameter of 'y' is interpreted as FALSE...
        $this->runHomeownersInsuranceReport = ( is_null( $input->getOption(self::HOMEOWNERS_INSURANCE_REPORT)) ? FALSE
                                        : ( strtolower($input->getOption(self::HOMEOWNERS_INSURANCE_REPORT)) == 'y' ? TRUE : FALSE ) );
        
        // default is FALSE, so anything other than parameter of 'y' is interpreted as FALSE...
        $this->runIncomeAffidavitReport = ( is_null( $input->getOption(self::INCOME_AFFIDAVIT_REPORT)) ? FALSE
                                        : ( strtolower($input->getOption(self::INCOME_AFFIDAVIT_REPORT)) == 'y' ? TRUE : FALSE ) );
             
        // default is FALSE, so anything other than parameter of 'y' is interpreted as FALSE...
        $this->runMDsDataEntryGapsReport = ( is_null( $input->getOption(self::MDS_DATA_ENTRY_GAPS_REPORT)) ? FALSE
                                        : ( strtolower($input->getOption(self::MDS_DATA_ENTRY_GAPS_REPORT)) == 'y' ? TRUE : FALSE ) );

        // default is FALSE, so anything other than parameter of 'y' is interpreted as FALSE...
        $this->runPennsouthShareholdersReport = ( is_null( $input->getOption(self::PENNSOUTH_SHAREHOLDERS_REPORT)) ? FALSE
                                                : ( strtolower($input->getOption(self::PENNSOUTH_SHAREHOLDERS_REPORT)) == 'y' ? TRUE : FALSE ) );


        // default is FALSE, so anything other than parameter of 'y' is interpreted as FALSE...
        $this->runReportOnAptsWithNoEmail = ( is_null( $input->getOption(self::REPORT_ON_APTS_WITH_NO_EMAIL)) ? FALSE
                                        : ( strtolower($input->getOption(self::REPORT_ON_APTS_WITH_NO_EMAIL)) == 'y' ? TRUE : FALSE ) );

        // default is FALSE, so anything other than parameter of 'y' is interpreted as FALSE...
        $this->runReportOnAptsWithNoShareholderEmail = ( is_null( $input->getOption(self::REPORT_ON_APTS_WITH_NO_SHAREHOLDER_EMAIL)) ? FALSE
                                               : ( strtolower($input->getOption(self::REPORT_ON_APTS_WITH_NO_SHAREHOLDER_EMAIL)) == 'y' ? TRUE : FALSE ) );

        // default is FALSE, so anything other than parameter of 'y' is interpreted as FALSE...
        $this->runReportOnAptsShareholderWantsMinutesHardCopy = ( is_null( $input->getOption(self::REPORT_ON_SHAREHOLDER_MINUTES_HARD_COPY)) ? FALSE
                                               : ( strtolower($input->getOption(self::REPORT_ON_SHAREHOLDER_MINUTES_HARD_COPY)) == 'y' ? TRUE : FALSE ) );



        $processCtr = 0;


        if ($this->runParkingLotReport) {
            print ("\n" . "run Parking Lot Report set to true. \n");
            $this->emailNotifyReportOrProcessName = self::PARKING_LOT_REPORT;
            $processCtr++;
        }
        else {
            print ("\n" . "run Parking Lot Report set to false. \n");
        }

        if ($this->runMdsChangeDetectionReport) {
            print ("\n" . "run MDS Change Detection Report set to true. \n");
            $this->emailNotifyReportOrProcessName = self::MDS_CHANGE_DETECTION_REPORT;
            $processCtr++;
        }
        else {
            print ("\n" . "run MDS Change Detection Report set to false. \n");
        }


        if ($this->runHomeownersInsuranceReport) {
            print ("\n" . "run Homeowners Insurance Report set to true. \n");
            $this->emailNotifyReportOrProcessName = self::HOMEOWNERS_INSURANCE_REPORT;
            $processCtr++;
        }
        else {
            print ("\n" . "run Homeowners Insurance Report set to false. \n");
        }

        if ($this->runIncomeAffidavitReport) {
            print ("\n" . "run Income Affidavit Report set to true. \n");
            $this->emailNotifyReportOrProcessName = self::INCOME_AFFIDAVIT_REPORT;
            $processCtr++;
        }
        else {
            print ("\n" . "run Income Affidavit Report set to false. \n");
        }
        
        if ($this->runMDsDataEntryGapsReport) {
            print ("\n" . "run MDS Data Entry Discrepancies Report set to true. \n");
            $this->emailNotifyReportOrProcessName = self::MDS_DATA_ENTRY_GAPS_REPORT;
            $processCtr++;
        }
        else {
            print ("\n" . "run MDS Data Entry Discrepancies Report set to false. \n");
        }

        if ($this->runPennsouthShareholdersReport) {
            print ("\n" . "run Pennsouth Shareholders Report set to true. \n");
            $this->emailNotifyReportOrProcessName = self::PENNSOUTH_SHAREHOLDERS_REPORT;
            $processCtr++;
        }
        else {
            print ("\n" . "run Pennsouth Shareholders Report set to false. \n");
        }

        if ($this->runReportOnAptsWithNoEmail) {
            print ("\n" . "Run Report on Apartments with No Email Address set to true. \n");
            $this->emailNotifyReportOrProcessName = self::REPORT_ON_APTS_WITH_NO_EMAIL;
            $processCtr++;
        }
        else {
            print ("\n" . "Run Report on Apartments with No Email Address set to false. \n");
        }

        if ($this->runReportOnAptsWithNoShareholderEmail) {
            print ("\n" . "Run Report on Apartments with No Shareholder Email Address set to true. \n");
            $this->emailNotifyReportOrProcessName = self::REPORT_ON_APTS_WITH_NO_SHAREHOLDER_EMAIL;
            $processCtr++;
        }
        else {
            print ("\n" . "Run Report on Apartments with No Shareholder Email Address set to false. \n");
        }

        if ($this->runReportOnAptsShareholderWantsMinutesHardCopy) {
                    print ("\n" . "Run Report on Apartments where Shareholder Wants Minutes Hard Copy set to true. \n");
                    $this->emailNotifyReportOrProcessName = self::REPORT_ON_SHAREHOLDER_MINUTES_HARD_COPY;
                    $processCtr++;
        }
        else {
            print ("\n" . "Run Report on Apartments where Shareholder Wants Minutes Hard Copy set to false. \n");
        }


        if ($processCtr > 1) {
            print("\n Command line parameters have been set to run more than one process / report in this run of the program. Please limit the run parameters to one process or report. \n");
            print("\n Program is exiting. Adjust parameters and resubmit.");
            exit(1);
        }

        // Seems to need 128M (32M default setting on Rose Hosting server is too little - Doctrine query runs out of memory in select from pennsouth_resident...
        $memory_limit = ini_get('memory_limit');
        print("\n memory_limit: " . $memory_limit);

        $this->defaultEmailNotifyParameters = new EmailNotifyParameters();
        $this->defaultEmailNotifyParameters->setRecipientEmailAddress(self::DEFAULT_ADMIN_EMAIL_RECIPIENT_ADDRESS);
        $this->defaultEmailNotifyParameters->setRecipientName(self::DEFAULT_ADMIN_EMAIL_RECIPIENT_NAME);
        $this->defaultEmailNotifyParametersArray[] = $this->defaultEmailNotifyParameters;



        try {
            $emailNotifyParametersReader = new EmailNotifyParametersReader($this->getEntityManager());
            $this->adminEmailNotifyRecipients = $emailNotifyParametersReader->getEmailNotifyParameters($this->emailNotifyReportOrProcessName);
        }
        catch( \Exception $exception) {
            print("\n" . "Exception occurred when invoking EmailNotifyParametersReader->getEmailNotifyParameters! \n");
            print ("Exception->getMessage() : " . $exception->getMessage() . "\n");
            print("\n" . "Exiting from program.");
            $subjectLine = "Fatal exception encountered in Pennsouth Reports Program";
            $messageBody =  "\n" . "Exception occurred! Exception->getMessage() : " . $exception->getMessage() . "\n";
            $messageBody .= "\n" . "Exception stack trace: " . $exception->getTraceAsString();
            $this->isExceptionRaised = TRUE;
            $this->sendEmailtoAdmins($subjectLine, $messageBody, $this->isExceptionRaised);
            exit(1);
        }
        //$emailNotificationLists = array();
        $account = null; // added this declaration 11/4/2016 when code was working without it - but then
                         // how could call to$aweberSubscriberListReader->getSubscribersToEmailNotificationList($account, $emailNotificationList) work without this declaration?
                         // question is does the variable declaration here break anything???


       // print("\n ------- 1 -----------\n");

        if ($this->runParkingLotReport) {
             try {
                 $phpExcel = $this->getContainer()->get('phpexcel');
                 $parkingLotReportCreator = new ManagementReportsWriter($this->getEntityManager(), $phpExcel, $appOutputDir, $env);
                 $parkingLotReportCreator->generateParkingLotList();
                 $subjectLine = "Pennsouth Parking Lot List Created.";
                 $messageBody = "\n The Pennsouth Parking Lot List spreadsheet has been created and is attached to this email. It is also available on the Pennsouth Ftp Server. \n";
                 $attachmentFilePath = $appOutputDir . "/" . ManagementReportsWriter::PARKING_LOT_LIST_FILE_NAME;
                 $this->sendEmailtoAdmins($subjectLine, $messageBody, $this->isExceptionRaised, $attachmentFilePath);
             }
             catch (\Exception $exception) {
                 print("\n Exception encountered when running the Parking Lot Report.");
                 print("\n Exception->getMessage(): " . $exception->getMessage());
                 print("\n stacktrace: " . $exception->getTraceAsString());
                 print("\n Exiting from program.");
                 $subjectLine = "Fatal exception encountered in Pennsouth Reports Program in section where Parking Lot Report is created.";
                 $messageBody =  "\n Exception->getMessage() : " . $exception->getMessage() . "\n";
                 $messageBody .= "\n" . "Exception stack trace: " . $exception->getTraceAsString();
                 $this->isExceptionRaised = TRUE;
                 $this->sendEmailtoAdmins($subjectLine, $messageBody, $this->isExceptionRaised);
                 exit(1);
             }
        }

        if ($this->runHomeownersInsuranceReport) {
             try {
                 $homeownersInsuranceReportCreator = new ManagementReportsWriter($this->getEntityManager(), null, $appOutputDir, $env);
                 $homeownersInsuranceReportCreator->createHomeownersInsuranceReport();
                 $subjectLine = "Pennsouth Homeowners Insurance Report Created.";
                 $messageBody = "\n The Pennsouth Homeowners Insurance Report has been created and is attached to this email. It is also available on the Pennsouth Ftp Server. \n";
                 $attachmentFilePath = $appOutputDir . "/" . ManagementReportsWriter::HOMEOWNERS_INSURANCE_REPORT_FILE_NAME;
                 $this->sendEmailtoAdmins($subjectLine, $messageBody, $this->isExceptionRaised, $attachmentFilePath);
             }
             catch (\Exception $exception) {
                 print("\n Exception encountered when running the Homeowners Insurance Report.");
                 print("\n Exception->getMessage(): " . $exception->getMessage());
                 print("\n stacktrace: " . $exception->getTraceAsString());
                 print("\n Exiting from program.");
                 $subjectLine = "Fatal exception encountered in Pennsouth Reports Program in section where the Homeowners Insurance Report is created.";
                 $messageBody =  "\n Exception->getMessage() : " . $exception->getMessage() . "\n";
                 $messageBody .= "\n" . "Exception stack trace: " . $exception->getTraceAsString();
                 $this->isExceptionRaised = TRUE;
                 $this->sendEmailtoAdmins($subjectLine, $messageBody, $this->isExceptionRaised);
                 exit(1);
             }
        }


        if ($this->runIncomeAffidavitReport) {
             try {
                 $incomeAffidavitReportCreator = new ManagementReportsWriter($this->getEntityManager(), null, $appOutputDir, $env);
                 $incomeAffidavitReportCreator->createIncomeAffidavitReport();
                 $subjectLine = "Pennsouth Income Affidavit Report Created.";
                 $messageBody = "\n The Pennsouth Income Affidavit Report has been created. It is available for download from the Pennsouth Ftp Server. \n";
                // $attachmentFilePath = $appOutputDir . "/" . ManagementReportsWriter::INCOME_AFFIDAVIT_REPORT_FILE_NAME;
                 $this->sendEmailtoAdmins($subjectLine, $messageBody, $this->isExceptionRaised);
             }
             catch (\Exception $exception) {
                 print("\n Exception encountered when running the Income Affidavit Report.");
                 print("\n Exception->getMessage(): " . $exception->getMessage());
                 print("\n stacktrace: " . $exception->getTraceAsString());
                 print("\n Exiting from program.");
                 $subjectLine = "Fatal exception encountered in Pennsouth Reports Program in section where the Income Affidavit Report is created.";
                 $messageBody =  "\n Exception->getMessage() : " . $exception->getMessage() . "\n";
                 $messageBody .= "\n" . "Exception stack trace: " . $exception->getTraceAsString();
                 $this->isExceptionRaised = TRUE;
                 $this->sendEmailtoAdmins($subjectLine, $messageBody, $this->isExceptionRaised);
                 exit(1);
             }
        }


        if ($this->runMdsChangeDetectionReport) {
             try {
                 $mdsChangeDetectionReportCreator = new MdsChangeDetectionReportWriter($this->getEntityManager(), null, $appOutputDir, $env);
                 $mdsChangeDetectionReportCreator->createMdsChangeDetectionReport();
                 $subjectLine = "MDS Change Detection Report Created.";
                 $messageBody = "\n The MDS Change Detection Report has been created. It is available for download from the Pennsouth Ftp Server. \n";
                 $this->sendEmailtoAdmins($subjectLine, $messageBody, $this->isExceptionRaised);
             }
             catch (\Exception $exception) {
                 print("\n Exception encountered when running the MDS Change Detection Report.");
                 print("\n Exception->getMessage(): " . $exception->getMessage());
                 print("\n stacktrace: " . $exception->getTraceAsString());
                 print("\n Exiting from program.");
                 $subjectLine = "Fatal exception encountered in Pennsouth Reports Program in section where the MDS Change Detection Report is created.";
                 $messageBody =  "\n Exception->getMessage() : " . $exception->getMessage() . "\n";
                 $messageBody .= "\n" . "Exception stack trace: " . $exception->getTraceAsString();
                 $this->isExceptionRaised = TRUE;
                 $this->sendEmailtoAdmins($subjectLine, $messageBody, $this->isExceptionRaised);
                 exit(1);
             }
        }
        
        
        if ($this->runMDsDataEntryGapsReport) {
             try {
                 $mdsDataEntryDiscrepanciesReportCreator = new ManagementReportsWriter($this->getEntityManager(), null, $appOutputDir, $env);
                 $gapsOrErrorsFound = $mdsDataEntryDiscrepanciesReportCreator->createMdsDataEntryGapsReport();
                 // only generate email if there were gaps/errors found; otherwise the empty report file is just saved to the ftp server.
                // print("\n gapsOrErrorsFound: " . ($gapsOrErrorsFound ? "true" : "false"));
                 if ($gapsOrErrorsFound) {
                     $subjectLine = "Pennsouth MDS Data Entry Gaps/Errors Report Created.";
                     $messageBody = "\n The Pennsouth MDS Data Entry Gaps/Errors Report has been created and is attached to this email. It is also available on the Pennsouth Ftp Server. \n";
                     $attachmentFilePath = $appOutputDir . "/" . ManagementReportsWriter::MDS_DATA_ENTRY_GAPS_REPORT_FILE_NAME;
                     $this->sendEmailtoAdmins($subjectLine, $messageBody, $this->isExceptionRaised, $attachmentFilePath);
                 }
             }
             catch (\Exception $exception) {
                 print("\n Exception encountered when running the MDS Data Entry Gaps/Errors Report.");
                 print("\n Exception->getMessage(): " . $exception->getMessage());
                 print("\n stacktrace: " . $exception->getTraceAsString());
                 print("\n Exiting from program.");
                 $subjectLine = "Fatal exception encountered in Pennsouth Reports Program in section where the MDS Data Entry Gaps/Errors Report is created.";
                 $messageBody =  "\n Exception->getMessage() : " . $exception->getMessage() . "\n";
                 $messageBody .= "\n" . "Exception stack trace: " . $exception->getTraceAsString();
                 $this->isExceptionRaised = TRUE;
                 $this->sendEmailtoAdmins($subjectLine, $messageBody, $this->isExceptionRaised);
                 exit(1);
             }
        }

        if ($this->runPennsouthShareholdersReport) {
                try {
                    $pennsouthShareholdersReportCreator = new ManagementReportsWriter($this->getEntityManager(), null, $appOutputDir, $env);
                    $pennsouthShareholdersReportCreator->createPennsouthResidentReport();
                    $subjectLine = "Pennsouth Shareholder Report Created.";
                    $messageBody = "\n The Pennsouth Shareholder Report has been created. It is available for download from the Pennsouth Ftp Server. \n";
                    $this->sendEmailtoAdmins($subjectLine, $messageBody, $this->isExceptionRaised);
                }
                catch (\Exception $exception) {
                    print("\n Exception encountered when running the Pennsouth Shareholder Report.");
                    print("\n Exception->getMessage(): " . $exception->getMessage());
                    print("\n stacktrace: " . $exception->getTraceAsString());
                    print("\n Exiting from program.");
                    $subjectLine = "Fatal exception encountered in Pennsouth Reports program in section where the Pennsouth Shareholder Report is created.";
                    $messageBody =  "\n Exception->getMessage() : " . $exception->getMessage() . "\n";
                    $messageBody .= "\n" . "Exception stack trace: " . $exception->getTraceAsString();
                    $this->isExceptionRaised = TRUE;
                    $this->sendEmailtoAdmins($subjectLine, $messageBody, $this->isExceptionRaised);
                    exit(1);
                }
           }
        


        if ($this->runReportOnAptsWithNoEmail) {
            try {
                $phpExcel = $this->getContainer()->get('phpexcel');
                $aptsWithNoResidentHavingEmailAddressListCreator = new AptsWithNoResidentHavingEmailReportWriter($this->getEntityManager(), $phpExcel, $appOutputDir, $env);
                $aptsWithNoResidentHavingEmailAddressListCreator->createSpreadsheetAptsWithNoEmailAddresses();
                $subjectLine = "List of Apartments With No Email Address Created.";
                $messageBody = "\n A document containing a list of apartments with no resident having an email address has been created. \n ";
                $messageBody .= " \n The spreadsheet is attached to this email. It is also available on the Pennsouth Ftp Server. \n";
                $attachmentFilePath = $appOutputDir . "/" . AptsWithNoResidentHavingEmailReportWriter::LIST_APTS_WITH_NO_EMAIL_ADDRESS_FILE_NAME;
                $this->sendEmailtoAdmins($subjectLine, $messageBody, $this->isExceptionRaised, $attachmentFilePath);
                exit(0);
            } catch (\Exception $exception) {
                print("\n Exception encountered when running the function to create a list of apartments where no resident has email.");
                print("\n Exception->getMessage(): " . $exception->getMessage());
                print("\n stacktrace: " . $exception->getTraceAsString());
                print("\n Exiting from program.");
                $subjectLine = "Fatal exception encountered in Pennsouth Reports Program in section where list of apartments with no email address is generated.";
                $messageBody = "\n Exception->getMessage() : " . $exception->getMessage() . "\n";
                $messageBody .= "\n" . "Exception stack trace: " . $exception->getTraceAsString();
                $this->isExceptionRaised = TRUE;
                $this->sendEmailtoAdmins($subjectLine, $messageBody, $this->isExceptionRaised);
                exit(1);
            }
        }

        if ($this->runReportOnAptsWithNoShareholderEmail) {
            try {
                $phpExcel = $this->getContainer()->get('phpexcel');
                $aptsWithNoShareholderHavingEmailAddressListCreator = new AptsWithNoShareholderHavingEmailReportWriter($this->getEntityManager(), $phpExcel, $appOutputDir, $env);
                $aptsWithNoShareholderHavingEmailAddressListCreator->createSpreadsheetAptsWithNoShareholderEmailAddresses();
                $subjectLine = "List of Apartments With No Shareholder Email Address Created.";
                $messageBody = "\n A document containing a list of apartments with no shareholder having an email address has been created. \n ";
                $messageBody .= " \n The spreadsheet is attached to this email. It is also available on the Pennsouth Ftp Server. \n";
                $attachmentFilePath = $appOutputDir . "/" . AptsWithNoShareholderHavingEmailReportWriter::LIST_APTS_WITH_NO_SHAREHOLDER_EMAIL_ADDRESS_FILE_NAME;
                // No need for notification
                // $this->sendEmailtoAdmins($subjectLine, $messageBody, $this->isExceptionRaised, $attachmentFilePath);
                exit(0);
            } catch (\Exception $exception) {
                print("\n Exception encountered when running the function to create a list of apartments where no shareholder has email.");
                print("\n Exception->getMessage(): " . $exception->getMessage());
                print("\n stacktrace: " . $exception->getTraceAsString());
                print("\n Exiting from program.");
                $subjectLine = "Fatal exception encountered in Pennsouth Reports Program in section where list of apartments with no shareholder email address is generated.";
                $messageBody = "\n Exception->getMessage() : " . $exception->getMessage() . "\n";
                $messageBody .= "\n" . "Exception stack trace: " . $exception->getTraceAsString();
                $this->isExceptionRaised = TRUE;
                $this->sendEmailtoAdmins($subjectLine, $messageBody, $this->isExceptionRaised);
                exit(1);
            }
        }


        if ($this->runReportOnAptsShareholderWantsMinutesHardCopy) {
            try {
                $phpExcel = $this->getContainer()->get('phpexcel');
                $aptsWhereShareholderWantsMinutesHardCopyCreator = new AptsWhereShareholderWantsOnlyMinutesHardCopyReportWriter($this->getEntityManager(), $phpExcel, $appOutputDir, $env);
                $aptsWhereShareholderWantsMinutesHardCopyCreator->createSpreadsheetAptsShareholderWantsMinutesHardCopy();
                $subjectLine = "List of Apartments Where Shareholder Wants Minutes Hard Copy Created.";
                $messageBody = "\n A document containing a list of apartments where shareholder wants Board Minutes hard copy has been created. \n ";
                $messageBody .= " \n The spreadsheet is attached to this email. It is also available on the Pennsouth Ftp Server. \n";
                $attachmentFilePath = $appOutputDir . "/" . AptsWhereShareholderWantsOnlyMinutesHardCopyReportWriter::LIST_APTS_WHERE_SHAREHOLDER_WANTS_MINUTES_HARD_COPY_FILE_NAME;
                // No need for notification
                // $this->sendEmailtoAdmins($subjectLine, $messageBody, $this->isExceptionRaised, $attachmentFilePath);
                exit(0);
            } catch (\Exception $exception) {
                print("\n Exception encountered when running the function to create a list of apartments where shareholder wants Board Minutes hard copy.");
                print("\n Exception->getMessage(): " . $exception->getMessage());
                print("\n stacktrace: " . $exception->getTraceAsString());
                print("\n Exiting from program.");
                $subjectLine = "Fatal exception encountered in Pennsouth Reports Program in section where list of apartments where shareholder wants Board Minutes hard copy is generated.";
                $messageBody = "\n Exception->getMessage() : " . $exception->getMessage() . "\n";
                $messageBody .= "\n" . "Exception stack trace: " . $exception->getTraceAsString();
                $this->isExceptionRaised = TRUE;
                $this->sendEmailtoAdmins($subjectLine, $messageBody, $this->isExceptionRaised);
                exit(1);
            }
        }



        $runEndDate = new \DateTime("now");
        print("\n" . "Program run end date/time: " . $runEndDate->format('Y-m-d H:i:s') . "\n");
        exit(0);


    }

    private function buildMessageBodyForEmailToAdmins($messageBody,  AweberUpdateSummary $aweberUpdateSummary, $errorMessages = null)
    {
        // if there are errorMessages, add them to the end of the message body.
        if (!is_null($errorMessages) and !empty($errorMessages)) {
            $messageBody .= "\n" . implode( "\n", $errorMessages);
        }

        return $messageBody;
    }

    private function sendEmailtoAdmins( $subjectLine, $messageBody, $isExceptionRaised = null, $attachmentFilePath = null) {
          $mailer = $this->getContainer()->get('mailer');
          $emailSubjectLine = $subjectLine;
          if (!is_null($this->adminEmailNotifyRecipients) and !empty($this->adminEmailNotifyRecipients)) {
              $emailRecipients = $this->adminEmailNotifyRecipients;
              print("\n" . "Sending to recipients obtained from database table email_notify_parameters ");
          }
          else {
              $emailRecipients = $this->defaultEmailNotifyParametersArray;
          }

          $emailBody = $messageBody;

          $emailer = new Emailer($mailer, $this->getContainer()->get('swiftmailer.transport.real'), $emailSubjectLine, $emailBody, $emailRecipients, $isExceptionRaised, $attachmentFilePath);


          $emailer->sendEmailMessage();

    }

}