<?php

class invoicer
{
	var $m_db;

	function invoicer($db) {
		$this->m_db = $db;	
	}

	//FIXME -- will need to add some "last invoice" flag so yearly don't get run every month
	function generate($userInfo, &$body, &$subject) {
		$uid = $userInfo->userid;

		if($userInfo->paymentType == 0)
			$userInfo->paymentType = 1;

		$sql = "select amount, hostingType, paymentType from paypal_payment_types
			where hostingType = $userInfo->hostingType and paymentType = $userInfo->paymentType";
		if(!$this->m_db->runQuery($sql) || $this->m_db->getNumRows() != 1) {
			$body .= "Generate invoice failed for $uid getting payment info.\n";	
			$subject .= " - Generate invoice failed";	
			return;
		}
		$typeInfo = $this->m_db->getRowObject();
		
		$this->m_db->begin();
		$sql = "INSERT INTO paypal_payment_invoices SET
				email = '$userInfo->email',
				  hostingType = '$userInfo->hostingType',
				  dueDate = NOW() + INTERVAL 10 day";


		if (!$this->m_db->runQuery($sql)) {
			$body .= "Generate invoice failed for $uid inserting invoice record.\n";	
			$subject .= " - Generate invoice failed";	
			$this->m_db->rollback();
			return;
		}
		$updateId = $this->m_db->getLastInsertId();
		
		$newBalance = (double)$userInfo->balance - (double)$typeInfo->amount;
		$sql = "update cc_user set
			balance = '$newBalance'
			where userid = '$uid'";
		if (!$this->m_db->runQuery($sql)) {
			$body .= "Generate invoice failed for $uid inserting invoice record.\n";	
			$subject .= " - Generate invoice failed";	
			$this->m_db->rollback();
			return;
		}
		$this->m_db->commit();
	
		//check to see if we have made a payment that hasn't been tied to an invoice
		//if so we can associate that that transaction with the newly created invoice
		 $sql = "select id, email, amount, txn_id, txn_type, invoiced from paypal_payment_history"
		        .   " where email = '$userInfo->email' and invoiced = '0'"
       			 .   " and (txn_type = 'web_accept' or txn_type = 'subscr_payment') order by payment_date asc limit 1";
		if (!$this->m_db->runQuery($sql) || $this->m_db->getNumRows() != 1) {
			$body .= "Generate invoice failed for $uid getting history.\n";	
			$subject .= " - Generate invoice failed";	
			return;
		}
		if($this->m_db->getNumRows() != 0) {
			$myTrans = $this->m_db->getRowObject();
			$this->m_db->begin();	
			$sql = "update paypal_payment_history set
				invoiced = 1 where id = " . $myTrans->id;

			if (!$this->m_db->runQuery($sql)) {
				$body .= "Generate invoice failed for $uid updating history.\n";	
				$subject .= " - Generate invoice failed";	
				$this->m_db->rollback();
				return;
			} 

			$sql = "update paypal_payment_invoices set
				paid = 1,
					 txn_id = '$myTrans->txn_id'
						 where id = $updateId";
			if (!$this->m_db->runQuery($sql)) {
				$body .= "Generate invoice failed for $uid updating invoice.\n";	
				$subject .= " - Generate invoice failed";	
				$this->m_db->rollback();
				return;
			}

			$newBalance = (double)$balance + (double)$myTrans->amount;
			$sql = "update cc_user set
				balance = '$newBalance'
				where userid = '$uid'";
			if (!$this->m_db->runQuery($sql)) {
				$body .= "Generate invoice failed for $uid updating balance.\n";	
				$subject .= " - Generate invoice failed";	
				$this->m_db->rollback();
				return;
			}
			$this->m_db->commit();	
		}

	}

	function processPayment($ipn_data, &$body, &$subject) {
		$email = $ipn_data['payer_email'];	
		$amount = $ipn_data['payment_gross'];	
		$payer_id = $ipn_data['payer_id'];	
		$item_name = $ipn_data['item_name'];	
		$item_number = $ipn_data['item_number'];	
		$pos = strpos($item_number, ",");
		$paymentType = substr($item_number, 0, $pos);
		$invoiceId = substr($item_number, $pos+1);
		$txn_type = $ipn_data['txn_type'];
		$txn_id = $ipn_data['txn_id'];

		$sql = "INSERT INTO paypal_payment_history SET
			email = '$email',
				  amount = '$amount',
				  payer_id = '$payer_id',
				  item_name = '$item_name',
				  item_number = '$item_number',
				  txn_type = '$txn_type',
				  txn_id = '$txn_id',
				  payment_date = NOW(),
				  data = '$body'";

		if(!$this->m_db->runQuery($sql)) {
			$body .= "Payment history failed.\n";	
			$subject .= " - Payment history failed";	
			return;
		}
		if($ipn_data['txn_type'] == 'web_accept' || $ipn_data['txn_type'] == 'subscr_payment') {
			if($ipn_data['payment_status'] != "Completed") 
				return;
		}
		$transId = $this->m_db->getLastInsertId();

		$sql = "select * from cc_user where email = '$email'";
		if(!$this->m_db->runQuery($sql) || $this->m_db->getNumRows() != 1) {
			$body .= "Balance update failed while getting user information.\n";	
			$subject .= " - Balance update failed";
			return;	
		}
		$row = $this->m_db->getRowObject();
	
		$balance = (double)$row->balance;
		
		$this->m_db->begin(); //begin transaction
		//			for($curRow=0; $curRow < $numRowsi && $balance < 0; ++$curRow) {

		$updateInvoice = true;	
		if($invoiceId == 0) {
			$sql = "select * from paypal_payment_invoices where paid = 0 and email = '$email' order by id asc limit 1";
			
			if(!$this->m_db->runQuery($sql)) {
				$body .= "Balance update failed while invoiced info.\n";	
				$subject .= " - Balance update failed";
				return;	
			}
			$numRows = $this->m_db->getNumRows();
			
			if($numRows == 1) {
				$firstInvoice = $this->m_db->getRowObject();
				$invoiceId = $firstInvoice->id;
			} else if ($numRows == 0) {
				$updateInvoice = false;
			}
		} 
	
		if($updateInvoice) {
			$sql = "update paypal_payment_history set
                invoiced = 1 where id = " . $transId;
			if (!$this->m_db->runQuery($sql)) {
				$body .= "Balance update failed updating payment history.\n";	
				$subject .= " - Balance update failed";
				$this->m_db->rollback();
				return;
			}
	
			$sql = "update paypal_payment_invoices set
					paid = 1,
					 txn_id = '$txn_id'
					 where id = $invoiceId";

			if (!$this->m_db->runQuery($sql)) {
				$body .= "Balance update failed updating payment invoice.\n";	
				$subject .= " - Balance update failed";
				$this->m_db->rollback();
				return;
			}
		}

		$newBalance = (double)$balance + (double)$amount;
		$sql = "update cc_user set
			balance = '$newBalance',
			paymentType = '$paymentType'
			where email = '$email'";
		if (!$this->m_db->runQuery($sql)) {
			$body .= "Balance update failed updating user balance.\n";	
			$subject .= " - Balance update failed";
			$this->m_db->rollback();
			return;
		}
		$this->m_db->commit();
	//}
	}

}

?>
