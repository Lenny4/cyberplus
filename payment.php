<?php
/*Génération de la session*/
/*session_id("session1"); //nomination de la session
session_start(); //Démareage du système de session*/

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

/**
 *        \file       htdocs/public/cmcic/payment.php
 *        \ingroup    cmcic
 *        \brief      File to offer a payment form for an invoice
 */

define("NOLOGIN", 1);        // This means this output page does not require to be logged.
define("NOCSRFCHECK", 1);    // We accept to go on this page from external web site.

$res = @include("../main.inc.php");                    // For root directory
if (!$res) $res = @include("../../main.inc.php");    // For "custom" directory

//Spécification des chemins pour le bas des pages

require_once(DOL_DOCUMENT_ROOT . "/core/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/security.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/date.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/functions.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/functions2.lib.php");
require_once DOL_DOCUMENT_ROOT . '/core/lib/payments.lib.php';
require_once(DOL_DOCUMENT_ROOT . "/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT . "/commande/class/commande.class.php");

dol_include_once('/cyberplus/class/cyberplus.class.php');

// Security check
if (empty($conf->cyberplus->enabled))
	accessforbidden('', 1, 1, 1);

$langs->load("main");
$langs->load("other");
$langs->load("dict");
$langs->load("bills");
$langs->load("companies");
$langs->load("errors");
$langs->load("cyberplus@cyberplus");

$key = GETPOST("key", 'alpha');

$error = false;
$message = false;

$cyberplus = new CyberPlus($db);
$result = $cyberplus->fetch('', $key);

if ($result <= 0) {
	$error = true;
	$message = $langs->trans('NoPaymentObject');
}

foreach (['TPE_MONETICO', 'THREE_D_SECURE_CHALLENGE_MONETICO', 'SOCIETE_MONETICO', 'KEY_MONETICO'] as $prop) {
	if (empty($conf->global->{$prop})) {
		$error = true;
		$message = $langs->trans('ConfigurationError');
		dol_syslog('CyberPlus: Configuration error : ' . $prop . ' is not defined');
	}
}

if (!$error) {
	$isInvoice = ($cyberplus->type == 'invoice' ? true : false);

	// Get societe info
	$societyName = $mysoc->name;
	$creditorName = $societyName;
	$currency = $conf->currency;

	// Define logo and logosmall
	$urlLogo = '';
	if (!empty($mysoc->logo_small) && is_readable($conf->mycompany->dir_output . '/logos/thumbs/' . $mysoc->logo_small)) {
		$urlLogo = DOL_URL_ROOT . '/viewimage.php?modulepart=mycompany&entity=1&file=logos%2' . urlencode('Fthumbs/' . $mysoc->logo_small);
	} elseif (!empty($mysoc->logo) && is_readable($conf->mycompany->dir_output . '/logos/' . $mysoc->logo)) {
		$urlLogo = DOL_URL_ROOT . '/viewimage.php?modulepart=mycompany&entity=1&file=logos%2' . urlencode($mysoc->logo);
	}

	// Prepare form
	$language = strtoupper($langs->getDefaultLang(true));
	//$language = strtoupper($langs->getDefaultLang(0));

	//$dateTransaction = dol_print_date(dol_now(), "%d/%m/%Y:%H:%M:%S");
	$dateTransaction = gmdate("YmdHis");
	$idTransaction = sprintf("%06d", rand(0, 899999));

	$bankName = 'Banque populaire';
	$urlServer = ($conf->global->API_TEST) ? $conf->global->CYBERPLUS_URL_SERVER_TEST : $conf->global->CYBERPLUS_URL_SERVER;
	$pay_conf_period = $conf->global->PAYMENT_CONF_PERIOD ? $conf->global->PAYMENT_CONF_PERIOD : ''; //modification GIDM
	$payment_id = $conf->global->PAYMENT_ID ? $conf->global->PAYMENT_ID : 0;
	$payment_id_2 = $conf->global->PAYMENT_ID_2 ? $conf->global->PAYMENT_ID_2 : 0;//modification GIDM
	$payment_id_3 = $conf->global->PAYMENT_ID_3 ? $conf->global->PAYMENT_ID_3 : 0;//modification GIDM
	$payment_id_4 = $conf->global->PAYMENT_ID_4 ? $conf->global->PAYMENT_ID_4 : 0;//modification GIDM

	$item = ($isInvoice) ? new Facture($db) : new Commande($db);

	$result = $item->fetch($cyberplus->fk_object);


	$alreadyPaid = 0;
	$creditnotes = 0;
	$deposits = 0;
	$totalObject = 0;
	$amountTransaction = 0;
	$nPayRef = 0;
	$needPayment = false;
	$result = $item->fetch_thirdparty($item->socid);

	if ($isInvoice) {
		$alreadyPaid = $item->getSommePaiement();
		$creditnotes = $item->getSumCreditNotesUsed();
		$deposits = $item->getSumDepositsUsed();
	}

	$totalObject = $item->total_ttc;

	$alreadyPaid = empty($alreadyPaid) ? 0 : $alreadyPaid;
	$creditnotes = empty($creditnotes) ? 0 : $creditnotes;
	$deposits = empty($deposits) ? 0 : $deposits;
	$nPayRef = empty($nPayRef) ? 0 : $nPayRef;

	$totalObject = empty($totalObject) ? 0 : $totalObject;

	$amountTransaction = $totalObject - ($alreadyPaid + $creditnotes + $deposits);

	$needPayment = ($item->statut == 1) ? true : false;

	// Do nothing if payment is already completed
	if (price2num($amountTransaction, 'MT') == 0 || !$needPayment) {
		$error = true;
		$message = ($isInvoice ? $langs->trans('InvoicePaymentAlreadyDone') : $langs->trans('OrderPaymentAlreadyDone'));
		dol_syslog('CyberPlus: Payment already completed, form will not be displayed');
		$a = 0;
	}
}

if (!$error) {
	$customerEmail = utf8_encode($item->thirdparty->email);
	$customerName = utf8_encode($item->thirdparty->name);
	$customerId = utf8_encode($item->thirdparty->id);
	$customerAddress = utf8_encode($item->thirdparty->address);
	$customerZip = utf8_encode($item->thirdparty->zip);
	$customerCity = utf8_encode($item->thirdparty->town);
	$customerCountry = utf8_encode($item->thirdparty->country_code);
	$customerPhone = utf8_encode($item->thirdparty->phone);

	//payment data
	$amountTransactionNum = intval(100 * price2num($amountTransaction, 'MT')); // Cents
	$nPayRef = $item->mode_reglement_id; //appel de l'element déclencheur du mode de réglement

	//création de la variable sélectionant le mode de réglement //modification GIDM
	if ($nPayRef == $payment_id_2) {
		$count = 2;
	} elseif ($nPayRef == $payment_id_3) {
		$count = 3;
	} elseif ($nPayRef == $payment_id_4) {
		$count = 4;
	} else {
		$count = 1;
	}

	//variable de première échéance //modification GIDM
	$x = 0;
	while ($x * $count < $amountTransactionNum) {
		$x = $x + 1;
	}
	$First = $x;

	$fields = array(
		'TPE' => $conf->global->TPE_MONETICO,
		'ThreeDSecureChallenge' => $conf->global->THREE_D_SECURE_CHALLENGE_MONETICO,
		"contexte_commande" => base64_encode(json_encode([
			'billing' => [
				'addressLine1' => mb_strimwidth(empty($adresse1) ? 'addressLine1' : $adresse1, 0, 50, ''),
				'city' => mb_strimwidth(empty($ctVille) ? 'city' : $ctVille, 0, 50, ''),
				'postalCode' => mb_strimwidth(empty($ctCodepostal) ? 'postalCode' : $ctCodepostal, 0, 10, ''),
				'country' => 'FR',
			],
		])),
		'date' => date('d/m/Y:H:i:s'),
		'lgue' => 'FR',
		"mail" => $customerEmail,
		"montant" => ($amountTransactionNum / 100) . "EUR",
		"reference" => $key,
		'societe' => $conf->global->SOCIETE_MONETICO,
		'texte-libre' => $cyberplus->fk_object,
		'url_retour_err' => dol_buildpath('/cyberplus/error.php', 2),
		'url_retour_ok' => dol_buildpath('/cyberplus/success.php', 2),
		'version' => '3.0',
	);

	ksort($fields);
	$paramHmac = [];
	foreach ($fields as $key => $value) {
		$paramHmac[] = $key . '=' . $value;
	}
	$filePath = __DIR__ . '/' . $conf->global->KEY_MONETICO;
	$mac = CyberPlus::computeHmac(implode('*', $paramHmac), file_get_contents($filePath));
	$fields['MAC'] = $mac;

	$fields2 = $fields;

	/*
	 * View
	 */
	$substit = array(
		'__OBJREF__' => $item->ref,
		'__SOCNAM__' => $societyName,
		'__SOCMAI__' => $conf->global->MAIN_INFO_SOCIETE_MAIL,
		'__CLINAM__' => $customerName,
		'__AMOINV__' => price2num($amountTransaction, 'MT')
	);

	if ($isInvoice) {
		$welcomeTitle = $langs->transnoentities('InvoicePaymentFormWelcomeTitle');
		$welcomeText = $langs->transnoentities('InvoicePaymentFormWelcomeText');
		$descText = $langs->transnoentities('InvoicePaymentFormDescText');
	} else {
		$welcomeTitle = $langs->transnoentities('OrderPaymentFormWelcomeTitle');
		$welcomeText = $langs->transnoentities('OrderPaymentFormWelcomeText');
		$descText = $langs->transnoentities('OrderPaymentFormDescText');
	}

	$welcomeTitle = make_substitutions($welcomeTitle, $substit);
	$welcomeText = make_substitutions($welcomeText, $substit);
	$descText = make_substitutions($descText, $substit);

	//sélection du template //modification GIDM
	if ($count == 1) {
		require_once('tpl/payment_1.tpl.php');
	} else {
		require_once('tpl/payment_2.tpl.php');
	}

} else {
	/*
	 * View
	 */
	$substit = array(
		'__SOCNAM__' => $conf->global->MAIN_INFO_SOCIETE_NOM,
		'__SOCMAI__' => $conf->global->MAIN_INFO_SOCIETE_MAIL,
	);

	$welcomeTitle = make_substitutions($langs->transnoentities('InvoicePaymentFormWelcomeTitle'), $substit);
	$message = make_substitutions($message, $substit);

	require_once('tpl/message.tpl.php');
}

//End of the page
htmlPrintOnlinePaymentFooter($mysoc, $langs, 1, $suffix, $object);
llxFooter('', 'public');

//fermer la connection à la base de donnée
$db->close();
