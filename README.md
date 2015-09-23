# Track-Your-iPhone
## Description: 
This is a light weight cross platform GPS track logging backend processor.
This version is written to work with an app in the Apple App Store 'Device Locator'. I
Am not affilitaed with the app in any way. I chose this one because of it runs in the backgound
of the iPhone and restarts itself after a reboot. Also, there is a web site were you can
enter your URL and arguments for THIS application. 

This is a working example that is fine for processing a handfull of iPhones. To track a
large amount or psuedo realtime tracking the architecture would be different in that 
you would not want the feed to be slowed down because of slow reverse geocoding calls
and the like.   

## Getting Started:
You could start by going to the Apple store and getting the App
https://itunes.apple.com/us/app/device-locator-track-locate/id380395093?ls=1&mt=8

However, you can go through the rest of the project and getting running before getting
the app.

## Prerequisites:
You must have a recent version of PHP installed (mine is 5.5.27)
You must have a recent version of MySQL installed (mine is 5.6.26)
You must have a web server installed (mine is Apache/2.4.16)
A MySQL admin interface or Query browser to set up the DB

These are all available in what is called a LAMP stack. 
LAMP -  Includes Linux, Apache, MySQL, and PHP/Python/Perl
Search google for lamp stack and the OS that you are running.

Once you have these programs running, download the deviceLocatorIpjone.php file to your
web servers home directory. Edit the file and put in you ipaddress, MySQL username and password.

Next download the myDB_09_22_2015.sql
then restore this Database using MySQLWorkBench, admin tool or from the command line.
Lets assume you have a root login on your local host and you downloaded the myDB_09_22_2015.sql
to your current directory enter the following and hit enter.
mysql -u root -p < myDB_09_22_2015.sql

You will be prompted for the password
and assuming you have the priveleges do create you should now have a myDB Database and trackLog Table
 
Now lets test it all:
bring up your browser and paste this in the address field:
http://127.0.0.1/deviceLocatorIphone.php?device=9999999999&long=-80.1236953735&lat=26.3995513916&acc=65.0&alt=0.0&altacc=-1.0&batt=0.9&ip=127.0.0.1&doDebug=1

If you note that for our test we have appended an additional argument at the end called doDebug.
doDebug is set to 1 will output debug information to the browser window for a local test.
If doDebug=2 it will output debug information to the specified log file

