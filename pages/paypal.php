<?php

include_once("pages/page.php");
include_once("classes/htmlFactory.php");
include_once("classes/paypal.class.php");  // include the class file

class paypal extends page
{
	var $m_action;
	var $m_caller;
	var $m_payer;

	function paypal($db) {
		//call parent ctor
		$this->page();
		$this->m_db = $db;
		$this->m_pageType = "paypal";

		$this->m_payer = new paypal_class;             // initiate an instance of the class
//		$this->m_payer->paypal_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';   // testing paypal url
		$this->m_payer->paypal_url = 'https://www.paypal.com/cgi-bin/webscr';     // paypal url

		$this->m_action = $_GET['action'];
		$this->m_caller = 'http://'.$_SERVER['HTTP_HOST'];
		//we select or information here so block info will be ready before we
		//get text
		GLOBAL $config;
//		$catId = $config['catId'];
		$sql = "select id, title, pageUrl, data, category, hideLeftBlocks, hideRightBlocks, minUserLevel  from " . $config['tableprefix']. "content where pageUrl = 'paypal'";

		$this->m_db->runQuery($sql);
		if($this->m_db->getNumRows() > 1)
			echo "Error getting page paypals";
		else { 
			$this->m_page = $this->m_db->getRowObject();
		}
	}

	function getDisplayText() {
		if($_SESSION['userLevel'] >= $this->m_page->minUserLevel) {
			if($this->m_action == "cancel") {
				$html = $this->cancel();
			} else if($this->m_action == "success") {
				$html = $this->success();
				$this->m_name = "list";
				$html .= $this->invoice();
			} else if($this->m_action == "invoice") {
				$html = $this->invoice();
			} else if($this->m_action == "pay") {
				$html = $this->paymentForm();
			} else {
				$this->m_name = "list";
				$html = $this->invoice();
			}
		} else { 
			$html .= "<p>You do not have access to this page...</p>\n";
		}

		return $html;
	}

	function invoice() {
		GLOBAL $config;
		$sql = "select userid, firstname, lastname, email, hostingType, paymentType,balance from " . $config['tableprefix']. "user where userid = '" .$_SESSION['uid'] . "'";

		$this->m_db->runQuery($sql);
		if($this->m_db->getNumRows() > 1)
			$html .= "Error getting hosting Type";
		else { 
			$row = $this->m_db->getRowObject();
			$hostingType = $row->hostingType;
			$paymentType = $row->paymentType;
			$firstInvoice = false;
			if($paymentType == 0) {
				$paymentType = 1;
				$firstInvoice = true;	
			}
			if($this->m_name == "list") {
				$sql = "select * from paypal_payment_invoices where email = '" .$row->email . "'";
				$this->m_db->runQuery($sql);
				$numRows = $this->m_db->getNumRows();
				$invoices = array();
				for($i=0; $i < $numRows; ++$i) {
					array_push($invoices, $this->m_db->getRowObject());
				}
				
				$sql = "select * from paypal_payment_types where hostingType=$hostingType and paymentType=$paymentType";

				$this->m_db->runQuery($sql);	
				if($this->m_db->getNumRows() != 1) {
					$html = "<p>Error retrieving payment type info.</p>";
					return $html;
				}
				$typeInfo = $this->m_db->getRowObject();
				$i=0;
				$html = html_formatGroup("Balance", "Current account balance: $". number_format((double)$row->balance, 2));
				$htmlBody = "<table width=\"100%\"><tr>\n";
				$htmlBody .= "\t<td>Invoice Id</td>\n";
				$htmlBody .= "\t<td>Due Date</td>\n";
				$htmlBody .= "\t<td>Amount</td>\n";
				$htmlBody .= "\t<td>Details</td>\n";
				$htmlBody .= "\t<td>Status</td>\n";
				$htmlBody .= "</tr>\n";
				foreach($invoices as $invoice) {
					if($i%2)
						$style = "class=\"listWhite\"";
					else 
						$style = "class=\"list\"";
					$htmlBody .= "<tr $style>\n";
					$htmlBody .= "\t<td>" . $invoice->id . "</td>\n";
					$htmlBody .= "\t<td>" . substr($invoice->dueDate,0, 10) . "</td>\n";
					$htmlBody .= "\t<td>" . $typeInfo->amount . "</td>\n";
					$htmlBody .= "\t<td><a href=\"paypal/invoice_detail_" . $invoice->id . ".html\">Display</a></td>\n";
					if($invoice->paid == 1 && $paymentType != 2)
						$status = "Paid";
					else if($paymentType == 2)
						$status = "Scheduled";
					else
						$status = "<a href=\"paypal/pay_invoice_" . $invoice->id . ".html\">Pay Now</a>\n";
					$htmlBody .= "\t<td>" . $status . "</td>\n";
					$htmlBody .= "</tr>\n";
					++$i;
				}
				$htmlBody .= "</table>\n";
				$html .= html_formatGroup("Invoices", $htmlBody);
			} else {
				if(1) { //$row->paymentType == 0) {
					$sql = "select * from paypal_payment_invoices where id = '" . $_GET['item'] . "'";
					$this->m_db->runQuery($sql);
					$numRows = $this->m_db->getNumRows();
					if($numRow != 0) {
						return "Error retrieving Invoice.";
					} else {
						$invoice = $this->m_db->getRowObject();
						$sql = "select * from paypal_payment_types where hostingType=$hostingType and paymentType=$paymentType";

						$this->m_db->runQuery($sql);	
						if($this->m_db->getNumRows() != 1) {
							$html = "<p>Error retrieving payment type info.</p>";
							return $html;
						}
						$typeInfo = $this->m_db->getRowObject();
						$title = "Invoice #" . $_GET['item'];
						$htmlBody .= "<p>Churchcontent.com<br /><hr /></p>";
						$htmlBody .= "<p>Name: $row->firstname $row->lastname </p>";
						$htmlBody .= "<p>Email: $row->email</p>";
						$htmlBody .= "<p>Order Information: <br /></p>";
						$htmlBody .= "<p></p>";
						$htmlBody .= "<p>Description: " . $typeInfo->desc . "</p>";
						$htmlBody .= "<p>Amount: \$" . $typeInfo->amount . "</p>";
						if($invoice->paid == 1)
							$htmlBody .= "<p><h3>Amount Due: \$0.00</h3></p>";
						else
							$htmlBody .= "<p><h3>Amount Due: \$" . $typeInfo->amount . "</h3></p>";

						$html = html_formatGroup($title, $htmlBody);
						if($firstInvoice) {
							$html .= "<p>As this is your first invoice, you have the following options when paying.  You can either pay each invoice manually on a monhtly basis, or set up a monthly recurring payment.  For further savings you can set up a single yearly payment that include a 10% discount.</p>";
						}
						if(!$invoice->paid)
							$html .= $this->paymentForm();
						if($paymentType == 2) 
							$html .= "<p>Payment has been scheduled, no action necessary</p>";

						$html .= "<a href=\"paypal/\">Back</a>";
						return $html;
					}	
				} else {
					$html = "Hmmm....how did you get here?";
				}	
			}
		}

		return $html;	

	}

	function paymentForm() {
		GLOBAL $config;
		$html = "<h3>" . $this->m_page->title ."</h3>";
				
		$html .= "<p>" . $this->m_page->data . "</p>";
		
		$sql = "select userid, hostingType, paymentType from " . $config['tableprefix']. "user where userid = '" .$_SESSION['uid'] . "'";
		$this->m_db->runQuery($sql);
		if($this->m_db->getNumRows() > 1)
			$html .= "Error getting hosting Type";
		else { 
			$row = $this->m_db->getRowObject();
			$hostingType = $row->hostingType;
			$paymentType = $row->paymentType;
			$sql = "select * from paypal_payment_types where hostingType = " . $hostingType;
//			if($paymentType != 0)
//				$sql .= " and paymentType = $paymentType";
			$this->m_db->runQuery($sql);	
			$numTypes = $this->m_db->getNumRows();
			if($numTypes <= 0 || $hostingType == 0)
				$html .= "Our records do no specify which type of hosting you currently have.  Please contact us for more information.";
			else { 
				$html .= "<div class=\"group\">";
				$html .= "<div class=\"groupTitle\">Payment Options</div>";
				$html .= "<form name=\"paypal_submit\" id=\"paypal_submit\" method=\"post\" action=\"pages/paypal_quiet.php?action=process\">\n";
	    		$html .= "<input name=\"hostingType\" type=\"hidden\" value=\"" . $hostingType . "\"  />\n";
				if(isset($_GET['item']) )
					$invoiceId = $_GET['item'];	
				else 
					$invoiceId = 0;	

	    		$html .= "<input name=\"invoiceId\" type=\"hidden\" value=\"" . $invoiceId . "\"  />\n";
				$html .= "<p>";
				for($i=0; $i<$numTypes; ++$i) {
					$typeInfo = $this->m_db->getRowObject();
					$html .= "<input type=\"radio\" name=\"paymentType\" value=\""
							. $typeInfo->paymentType . " \"> " 
							. $typeInfo->desc . " -- " . $typeInfo->amount . "<br />";
				}
			}
			$html .= "</p>";
			$html .= "<input type=\"submit\" name=\"cancel\" value=\"Cancel\" />\n";
			$html .= "<input type=\"submit\" name=\"submit\" value=\"Submit Payment\" />\n";
			$html .= "</form></div>";
		}

		return $html;
	}

	function success() {
		 return "<p>We have successfully received your payment via paypal.  It should post within the next few minutes. Thank you for using Churchcontent.com</p>";
	}
	function cancel() {
		return "<p>Payment canceled.  Click <a href=\"paypal/\">here</a> to attempt the payment again.";
	}

}

?>
