Dirty migration tools to help Elgg plugin devs migrate to Elgg 1.8.
===================================================================

Elgg 1.8 introduces some changes to how plugins need to be written.  This is
a collection of command line tools to help plugin authors convert to 1.8.

Files
-----------------------

* update_manifest.php - Updates 1.7 and lower manifest files to the 1.8 format. 
  * Usage: php update_manifest.php manifest.xml "Friendly Plugin Name"
