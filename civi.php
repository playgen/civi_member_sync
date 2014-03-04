<?php

civicrm_wp_initialize( );

$MembershipTypeDetials=civicrm_api("MembershipType","get", array ('version' => '3','sequential' =>'1'));
foreach( $MembershipTypeDetials['values'] as $key => $values){
	$MembershipType[$values['id']] = $values['name'];
}


$MembershipStatusDetials=civicrm_api("MembershipStatus","get", array ('version' => '3','sequential' =>'1'));
foreach( $MembershipStatusDetials['values'] as $key => $values){
	$MembershipStatus[$values['id']] =$values['name'];
}
