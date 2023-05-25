<?php
/* Copyright (C) 2012      Mikael Carlavan        <mcarlavan@qis-network.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */



define("NOLOGIN",1);		// This means this output page does not require to be logged.
define("NOCSRFCHECK",1);	// We accept to go on this page from external web site.

$res=@include("../main.inc.php");					// For root directory
if (! $res) $res=@include("../../main.inc.php");	// For "custom" directory

require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php"); //permet l'appel des informations de statut juridique et nom de société
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php'; //permet l'appel des informations de capital et n°siret

// Security check
if (empty($conf->cyberplus->enabled)) 
    accessforbidden('',1,1,1);
    
    
$langs->load("main");
$langs->load("other");
$langs->load("dict");
$langs->load("cyberplus@cyberplus");

// Define logo and logosmall
$urlLogo = '';
if (!empty($mysoc->logo_small) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_small))
{
	$urlLogo = DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&entity=1&file=logos%2'.urlencode('Fthumbs/'.$mysoc->logo_small);
}
elseif (! empty($mysoc->logo) && is_readable($conf->mycompany->dir_output.'/logos/'.$mysoc->logo))
{
	$urlLogo = DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&entity=1&file=logos%2'.urlencode($mysoc->logo);
}

$substit = array(
    '__SOCNAM__' => $conf->global->MAIN_INFO_SOCIETE_NOM,
    '__SOCMAI__' => $conf->global->MAIN_INFO_SOCIETE_MAIL,
);

$welcomeTitle = make_substitutions($langs->transnoentities('InvoicePaymentFormWelcomeTitle'), $substit);
$message = make_substitutions($langs->transnoentities('InvoicePaymentCanceled'), $substit);

/*
 * View
 */
require_once('tpl/message.tpl.php');

//End of the page
htmlPrintOnlinePaymentFooter($mysoc, $langs, 1, $suffix, $object);

llxFooter('', 'public');

?>