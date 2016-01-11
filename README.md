# polybrary
A hopefully user friendly library system to keep track of your books and other items.

Written with [Polymer 1.0](https://www.polymer-project.org/1.0/).

##### Dependencies
* Polymer 1.0
  * paper-elements
  * iron-elements
  * neon-elements
* MySQL server
* php
* [phpCAS](https://wiki.jasig.org/display/casc/phpcas)
* [php-barcode](https://github.com/davidscotttufts/php-barcode)
* [rasterizehtml](http://cburgmer.github.io/rasterizeHTML.js/)

##### Extra files
  This library code will reference a few files that are not included in this git repo.
* /bower_components/
* /node_modules/
* /CAS-1.3.4/
* /certificates/
  * cascert.pem
* /dashboard/includes/
  * casconfig.php
  * dbconfig.php
  * dbtables.php
  * uiconfig.php
* /dashboard/images/
  * label_logo.svg
* /dashboard/php-barcode/
  
  If you have any issues setting up feel free to send me an email!
  
##### A Forewarning
  Polybrary has been tested somewhat thoroughly for bugs but does not guarentee a flawless and error free experience. Use with caution.
  If you decide to use this project on a publicly hosted web server secured over HTTPS and uses CAS authentication, 
  please also know Polybrary has been tested for this security setup but again, does not guarentee an issue free experience.
  
  
##### Future Plans
* Merge edit and remove tiles for books and patrons.
* Add library hours and admin hours/check in.
* Create a patron tile for the browse section.
* Support vhs, dvd, blueray, etc items.

#### Changelog

Version 1.0.0
*Features: 
  * Books: browse, add, edit, remove, delete, check in, check out
  * Patrons: add, edit, remove, delete
  * Settings: add, delete admins, download/generate labels
  
