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

/**
 *        \file       htdocs/public/cmcic/tpl/message.php
 *        \ingroup    cmcic
 */

if (empty($conf->cyberplus->enabled))
	exit;

/*header('Content-type: text/html; charset=utf-8');*/

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta name="robots" content="noindex,nofollow"/>
	<title><?php echo $langs->trans('PaymentFormTitle'); ?></title>
	<link rel="stylesheet" type="text/css"
		  href="<?php echo DOL_URL_ROOT . $conf->css . '?lang=' . $langs->defaultlang; ?>"/>
	<style type="text/css">
		body {
			width: 50%;
			margin: auto;
			text-align: center;
		}

		#logo {
			width: 100%;
			margin: 30px 0px 30px 0px;
		}

		#payment-content {
			width: 100%;
			text-align: center;
		}

		.payment-button {
			text-align: right;
		}

		#tablepay, td {
			border: 1px solid black;
			border-collapse: collapse;
			text-align: center;

		}

		#tablepay {
			width: 70%;
			height: 25%;
			margin: auto;
			background-color: white;
		}

		.divtab {
			background-color: gray;
			margin: 0 auto;
		}
	</style>
</head>

<body>
<div id="logo">
	<?php if (!empty($urlLogo)) { ?>
		<img id="paymentlogo" title="<?php echo $societyName; ?>" src="<?php echo $urlLogo; ?>" alt=""/>
	<?php } ?>
</div>
<div id="payment-content">
	<h1>choisissez votre mode de paiement</h1>
</div>
<div class="divtab">
	<table id="tablepay">
		<tr>

			<td>
				<form action="<?php echo $urlServer; ?>" method="post" id="PaymentRequest">

					<input type="hidden" name="signature" value="<?php echo $signature; ?>"/>
					<input class="button" type="submit" value="<?php echo $langs->trans('paiement en une fois'); ?>"
						   onclick="openForm()"/>
					<!--<a href="../tpl/payment.tpl.php/?urlServer="><img src="..\img\Logo_1fois.png" alt="Paiement en une fois" width="100px" height="70px" ></a>-->
			</td>
			<td><a><img src="..\img\Logo_4fois.png" alt="Paiement en plusieurs fois" width="100px" height="70px"></a>
			</td>
		</tr>
	</table>
</div>
<!--<div id="pop">
<iframe name="iframe" height="90%"  frameborder="0" id="idframe" scrolling="no"></iframe>
</div>-->
</body>
</html>
