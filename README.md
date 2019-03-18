pennsouth_reports
================

A Symfony project created in the fall of 2017 by Stephen Frizell for Penn South Coop Apartment Complex.

This project breaks out much of the reporting functionality that was originally included in the pennsouth_aweber project into a separate project designed specifically for reporting. 
This should make the code more easily maintained and manageable. 

Depending on command-line arguments passed to the program, the application generates reports based on the nightly file of Pennsouth Resident data ftp'd nightly from MDS to Pennsouth's RoseHosting account:

See below for details of command-line arguments and their meaning.

This project is written in PHP and leverages the PHP Symfony framework's Console component to implement its functionality from the command line.

The application is invoked by issuing the following command from the project's root directory (i.e., pennsouth_reports) at the command line:
 php app/console app:pennsouth-reports
 
Issuing the following command will display the command line options for the application:

 php app/console app:pennsouth-reports -h
 
The scripts to process the nightly MDS export file are stored in the pennsouth-db-prep project on github (see: https://github.com/sfrizell/pennsouth-db-prep ) 

Presented below are all command line options/arguments:

**Options:**
  
+  \-p, --parking-lot-report=y/n                                 Option to create Parking Lot Report: y/n \[default: "n"\]

+ \-i, --homeowners-insurance-report=y/n                         Option to create Homeowners Insurance Report: y/n [default: "n"]

+ \-c, --income-affidavit-report=y/n                             Option to create Income Affidavit Report: y/n [default: "n"]

+ \-d, --mds-data-entry-gaps-report=y/n                             Option to create Income Affidavit Report: y/n [default: "n"]

+  \-b, --report-on-apts-where-no-resident-has-email-address=y/n  Option to generate spreadsheet listing apts where no resident has email address.: y/n \[default: "n"\]

+ \-r, --pennsouth-shareholders-report=y/n                             Option to create Penn South Shareholders Report (a list of all Penn South Shareholders): y/n [default: "n"]

+ \-r, --report-on-apts-where-no-shareholder-has-email-address=y/n   Option to create Penn South Shareholders With No Email + Shareholders Wanting Hard-Copy of All Communications Report: y/n [default: "n"]

+ \-r, --report-on-apts-shareholder-wants-minutes-hard-copy=y/n   Option to create Penn South Shareholders With No Email + Shareholders Only Wanting Hard-Copy of Board Minutes Report: y/n [default: "n"]



**Example:**

php app/console app:pennsouth-reports \-r y  

The above command will run the application with the option to create the Penn South Shareholders Report.
 
For better readability, the same command could be issued as follows:

php app/console app:pennsouth-reports \-\-pennsouth-shareholders-report=y


Developers Section
------------------

The main logic of the application is defined in the php class ProgramExecuteCommand in the src/Pennsouth/MdsBundle/Command directory.

As with other Symfony projects, configuration settings are to be found in the app/config folder.

All custom-written code for this project is located within the src folder under the project root directory.

Symfony framework components and other third-party libraries are located in the vendor directory under the project root directory.

Other than the components that are part of the symfony framework, the following additional third party libraries are used:


+ phpoffice, providing functionality to create excel documents, located in vendor/phpoffice, with a wrapper for using this library in symfony located in vendor/liuggio.

*NOTE:*
For security reasons, the repository does not include the 'parameters.yml' file (in app/config directory) required for the project to run. 
 



