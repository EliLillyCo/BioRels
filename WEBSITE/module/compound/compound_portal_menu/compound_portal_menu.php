<?php
global $USER_INPUT;



changeValue("compound_portal_menu", "COMPOUND_NAME", $USER_INPUT['PORTAL']['VALUE']);

if (isset($USER_INPUT['PORTAL']['TYPE'])) {
	changeValue("compound_portal_menu", "COMPOUND_TYPE", $USER_INPUT['PORTAL']['TYPE']);
} else changeValue("compound_portal_menu", "COMPOUND_TYPE", "COMPOUND");
