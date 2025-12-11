<?php
require_once dirname(dirname(dirname(__FILE__))) . "/DL/DAL.php";

use data\TableItem;

class royalty extends TableItem
{
	// fields
	public $ID;
	public $dsp;
	public $customerID;
	public $upc;
	public $isrc;
	public $country;
	public $unit;
	public $currency;
	public $amountDue;
	public $rate;
	public $accountPaidwithoutVat;
	public $accountPaid;
	public $scopeDate;
	public $scopeShortDate;
	public $paymentCurrency;
	public $albumIds;
	public $type;
	public $title;




	// Counctructor
	function __construct($ID = NULL)
	{
		parent::__construct();
		$this->ID = $ID;
		$this->settable("royalty");
		$this->refresh($ID);
	}
	function __set($property, $value)
	{
		$this->$property = $value;
	}
	function __get($property)
	{
		if (isset($this->$property)) {
			return $this->$property;
		}
	}

	function getCumulativeRoyalty($userID)
	{
		$sql = "call getCumulativeRoyalty($userID)";
		return $this->executenonquery($sql, true);
	}

	//Earnings - Summary
	function getRoyaltySummary($userID)
	{
		$sql = "call pgetRoyaltySummary($userID)";
		return $this->executenonquery($sql, true);
	}

	//Earnings - Chart
	function getRoyaltyGraph($userID, $currency)
	{
		$sql = "call pgetRoyaltyGraph($userID, '$currency')";
		return $this->executenonquery($sql, true);
	}

	//Create Invoice Currecy
	function createInvoiceCurrency($userID, $scopeDate, $tryusd, $tryeur, $usdtry, $usdeur, $eurtry, $eurusd, $trygbp, $gbptry, $usdgbp, $gbpusd, $eurgbp, $gbpeur)
	{
		$tryusd = str_replace(",", ".", $tryusd);
		$tryeur = str_replace(",", ".", $tryeur);
		$usdtry = str_replace(",", ".", $usdtry);
		$usdeur = str_replace(",", ".", $usdeur);
		$eurtry = str_replace(",", ".", $eurtry);
		$eurusd = str_replace(",", ".", $eurusd);
		$trygbp = str_replace(",", ".", $trygbp);
		$gbptry = str_replace(",", ".", $gbptry);
		$usdgbp = str_replace(",", ".", $usdgbp);
		$gbpusd = str_replace(",", ".", $gbpusd);
		$eurgbp = str_replace(",", ".", $eurgbp);
		$gbpeur = str_replace(",", ".", $gbpeur);

		$sql = "call pCreateInvoiceCurrency($userID, '$scopeDate', $tryusd, $tryeur, $usdtry, $usdeur, $eurtry, $eurusd,$trygbp,$gbptry,$usdgbp,$gbpusd,$eurgbp,$gbpeur)";
		$this->executenonquery($sql, null, true);
		return 1;
	}

	function createInvoices($copeDate, $invStatusID)
	{

		$sql = "call pCreateInvoices('$copeDate')";
		return $this->executenonquery($sql, true);
	}

	//Earnings - Platforms & Dashboard - Platforms
	function getRoyaltyPlatforms($userID, $platform = null, $period = 1, $recordLimit = 18446744073709551615) //recordLimit = Bigint
	{
		$sql = "call pgetRoyaltyPlatforms($userID, '$platform', $period, $recordLimit);";
		return $this->executenonquery($sql, true);
	}

	//Earnings - Invoices
	function getRoyaltyInvoices($userID, $period = 1)
	{
		$sql = "call pgetRoyaltyInvoices($userID, $period);";
		//echo $sql;
		return $this->executenonquery($sql, true);
	}

	//Summary - Invoices
	function getMainRoyalties($userID, $countryID = 236, $period = 1)
	{
		$sql = "call getMainRoyalties($userID, $countryID, $period);";
		//echo $sql;
		return $this->executenonquery($sql, true);
	}

	//Earnings - Download Invoices
	function getRoyaltyInvoicesDetails($scopeDate, $userID)
	{
		$sql = "call pgetRoyaltyDetails('" . $scopeDate . "'," . $userID . ")";
		return $this->executenonquery($sql, true);
	}

	// pgetRoyaltyDetailsWithUser
	function getRoyaltySublabelInvoicesDetails($scopeDate, $userID)
	{
		$sql = "call pgetRoyaltyDetailsWithUser('" . $scopeDate . "'," . $userID . ")";
		return $this->executenonquery($sql, true);
	}

	function getRoyaltyDetails($scopeDate, $userID)
	{
		$sql = "call pgetRoyaltyDetailsForPanel('" . $scopeDate . "'," . $userID . ")";
		return $this->executenonquery($sql, true);
	}

	//Dashboard - Top Performing Products
	function getRoyaltyProducts($labelUserId)
	{
		$sql = "call pgetRoyaltyProducts(" . $labelUserId . ")";
		return $this->executenonquery($sql, true);
	}

	function processInvoice($status, $statusId, $processDate)
	{

		//echo $status."-".$processDate;
		//exit();

		switch ($status) {
			case 1: // Fatura Onay

				//Aylık Fatura Onay yapıldığında o aya ait konsolide royalty aktarımı da yapılır
				//$query = "call proyaltyTransfer('".$processDate."-01')";
				//$rCumulative = $this->executenonquery($query, true);									

				$query = "update invoiceStatuses set status=1,statusDate=now() where ID = " . $statusId;
				return $this->executenonquery($query, null, true);

				break;
			case 2: // Fatura Onay Geri Al

				//Aylık Fatura Onay Geri Al yapıldığında o aya ait konsolide data da royalty den silinir.
				//$query = "delete from royalty where date_format(royalty.startDate, '%Y-%m') = '".$processDate."'";
				//$dRoyalty = $this->executenonquery($query, true);

				$query = "update invoiceStatuses set status=0,statusDate=now() where ID = " . $statusId;
				return $this->executenonquery($query, null, true);

				break;
			case 3: // Fatura Sil

				//Aylık Fatura Onay Silme yapıldığında o aya ait konsolide data da royalty den silinir.
				//$query = "delete from royalty where date_format(royalty.startDate, '%Y-%m') = '".$processDate."'";
				//$dRoyalty = $this->executenonquery($query, true);
				$query = "delete from invoices where invoiceStatusID=" . $statusId;
				$this->executenonquery($query, null, true);

				$query = "delete from royalty where date(scopeDate) = '" . $processDate . "-01'";
				$this->executenonquery($query, null, true);

				$query = "delete from invoiceStatuses where invoiceStatuses.ID=" . $statusId;
				return $this->executenonquery($query, null, true);
				break;
			default:
				return 0;
				break;
		}
	}

	function processInvoiceStatus($chk, $invoiceId, $status)
	{
		switch ($status) {
			case 4: // Fatura Kesildi
				$query = "update invoices set invoice = $chk where ID = $invoiceId";
				return $this->executenonquery($query, null, true);
				break;
			case 5: // Odendi
				$query = "update invoices set paid=$chk where ID = $invoiceId";
				return $this->executenonquery($query, null, true);
				break;
			default:
				return 0;
				break;
		}
	}

	function pgetRoyaltyInvoicesDetails($roleID, $customerID, $period, $userID = 0)
	{
		$sql = "call pgetRoyaltyInvoicesDetails($roleID, $customerID, '$period',$userID)";
		return $this->executenonquery($sql, true);
	}

	function getPeriods()
	{
		$sql = "call pgetPeriods()";
		return $this->executenonquery($sql, true);
	}

	function getRoyaltyFx($scopeDate)
	{
		$sql = "call getRoyaltyFx('$scopeDate')";
		return $this->executenonquery($sql, true);
	}
}
