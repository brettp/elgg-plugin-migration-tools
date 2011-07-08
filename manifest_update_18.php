<?php
/**
 * Update a 1.7 or lower manifest to 1.8 style
 *
 * Usage: php manifest_update.php manifest.xml <friendly_name>
 *
 * Creates manifest.xml.bak and updates live manifest.xml
 */

if ($argc < 3) {
	die("Must specify the manifest file and the friendly plugin name.\n");
}

$manifest_file = $argv[1];

$manifest = file_get_contents($manifest_file);
$friendly_name = $argv[2];

if (!$manifest) {
	die("Could not open manifest file.\n");
}

$manifest = load_plugin_manifest($manifest);

if (!$manifest) {
	die("Could not parse manifest.\n");
}

$new_manifest = <<<___XML
<?xml version="1.0" encoding="UTF-8" ?>
<plugin_manifest xmlns="http://www.elgg.org/plugin_manifest/1.8">
	<name>$friendly_name</name>

___XML;

// section it out  a bit
$reqs = array();
$admin = array();

foreach ($manifest as $name => $value) {
	switch ($name) {
		case 'elgg_install_state':
			if ($value == 'enabled') {
				$admin[] = "\t<activate_on_install>true</activate_on_install>\n";
			}
			break;

		case 'admin_interface':
			$admin[] = "\t<$name>$value</$name>\n";
			break;

		case 'elgg_version':
			$reqs[] = <<<___REQ
   	<requires>
		<type>elgg_version</type>
		<version>$value</version>
	</requires>

___REQ;
		break;

		// name is required to be specified manually
		case 'name':
			break;

		default:
			$new_manifest .= "\t<$name>$value</$name>\n";
			break;
	}
}

if ($admin) {
	$new_manifest .= "\n";

	foreach ($admin as $item) {
		$new_manifest .= $item;
	}
}

if ($reqs) {
	$new_manifest .= "\n";

	foreach ($reqs as $item) {
		$new_manifest .= $item;
	}
}

$new_manifest .= "</plugin_manifest>\n";

if (!rename($manifest_file, "$manifest_file.bak")) {
	die("Could not save backup file.  Copy and paste:\n\n$new_manifest");
}

$h = fopen($manifest_file, 'wb');

if (!$h) {
	die("Could not open manifest file for writing.  Copy and paste:\n\n$new_manifest");
}

if (!fwrite($h, $new_manifest)) {
	die("Could not write to manifest file.  Copy and paste:\n\n$new_manifest");
}



/**
 * Parses the manfiest into an array
 *
 * @param string $manifest
 * @return mixed
 */
function load_plugin_manifest($manifest) {
	$xml = xml_to_object($manifest);

	if ($xml) {
		$elements = array();

		foreach ($xml->children as $element) {
			$key = $element->attributes['key'];
			$value = $element->attributes['value'];

			$elements[$key] = $value;
		}

		return $elements;
	}

	return false;
}

/**
 * Parse an XML file into an object.
 * Based on code from http://de.php.net/manual/en/function.xml-parse-into-struct.php by
 * efredricksen at gmail dot com
 *
 * @param string $xml The XML
 *
 * @return object
 */
function xml_to_object($xml) {
	$parser = xml_parser_create();

	// Parse $xml into a structure
	xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
	xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
	xml_parse_into_struct($parser, $xml, $tags);

	xml_parser_free($parser);

	$elements = array();
	$stack = array();

	foreach ($tags as $tag) {
		$index = count($elements);

		if ($tag['type'] == "complete" || $tag['type'] == "open") {
			$elements[$index] = new stdClass();
			$elements[$index]->name = $tag['tag'];
			$elements[$index]->attributes = (isset($tag['attributes'])) ? $tag['attributes'] : '';
			$elements[$index]->content = (isset($tag['value'])) ? $tag['value'] : '';

			if ($tag['type'] == "open") {
				$elements[$index]->children = array();
				$stack[count($stack)] = &$elements;
				$elements = &$elements[$index]->children;
			}
		}

		if ($tag['type'] == "close") {
			$elements = &$stack[count($stack) - 1];
			unset($stack[count($stack) - 1]);
		}
	}

	return $elements[0];
}
