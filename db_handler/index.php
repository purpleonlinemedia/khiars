<?php
#Composer auto loader
require_once 'vendor/autoload.php';
#Composer package Respect/Validator
use Respect\Validation\Validator as v;

class db_object
{        
    public $conn;
    #For heirarchy
    public $returnIntroducerNo = 0;   
    
    #Construct the class
    public function __construct() 
    {
        include 'include/config.php';      
    
        #Instantiate the database connection
        $this->conn = mysqli_connect($db_host,$db_user,$db_pass,$db_name);				
        if ($this->conn->connect_errno) {            
             $xmlstr = <<<_XML_
<?xml version='1.0' standalone='yes'?>
 <returnCall>
  <status>Fail</status>                      
  <msg>Failed database connection</msg>                      
  <errorMsg>$this->conn->connect_error</errorMsg>                      
 </returnCall>

_XML_;
            return $xmlstr;
            
        }          
    }        
    
   #############################################################
   #Class functions
   #############################################################
   ##############################paymentDates###########################################
   function paymentDates($contractNo,$startDate)
   {       
       #Validate
       if(!v::numeric()->validate($contractNo))#Failed validation
       {
           $xmlstr = <<<_XML_
<?xml version='1.0' standalone='yes'?>
 <returnCall>
  <status>Fail</status>                      
  <msg>Invalid contract number</msg>                      
 </returnCall>

_XML_;

         return $xmlstr;
         
       }
                     
       if(!v::date()->validate($startDate))#Failed validation
       {           
           $xmlstr = <<<_XML_
<?xml version='1.0' standalone='yes'?>
 <returnCall>
  <status>Fail</status>                      
  <msg>Invalid date format</msg>                      
 </returnCall>

_XML_;

         return $xmlstr;          
         
       }              
              
       #Get contract details                       
       $resultSet = mysqli_query($this->conn,"SELECT joiningFeePlan FROM accountrecords WHERE contractNo = '".mysqli_real_escape_string($this->conn,$contractNo)."';",MYSQLI_STORE_RESULT);
       if(mysqli_errno($this->conn) != 0)
       {
           #Failed to run query
           $xmlstr = <<<_XML_
<?xml version='1.0' standalone='yes'?>
 <returnCall>
  <status>Fail</status>                      
  <msg>Failed to execute query</msg>          
  <errorMsg>$this->conn->error<errorMsgmsg>             
 </returnCall>

_XML_;
           return $xmlstr;
           
       }
             
       $row_cnt = mysqli_num_rows($resultSet);
       //$row_cnt = mysqli_num_rows($this->conn,$resultSet);
       if($row_cnt == 0)
       {
           $xmlstr = <<<_XML_
<?xml version='1.0' standalone='yes'?>
 <returnCall>
  <status>Fail</status>                      
  <msg>Contract does not exist -> $row_cnt</msg>                        
 </returnCall>

_XML_;
           return $xmlstr;
           
       }
       
       $returnRow = mysqli_fetch_assoc($resultSet);
       
       #Get the commission date
       if($returnRow['joiningFeePlan'] == 1)
       {
            $commissiondate = strtotime ( '+1 month' ,strtotime($startDate)) ;
            $commissiondate = date("Y-m-d",$commissiondate);
       }
       elseif ($returnRow['joiningFeePlan'] == 2)
       {
            $commissiondate = strtotime ( '+2 month' ,strtotime($startDate)) ;    
            $commissiondate = date("Y-m-d",$commissiondate);
       }
       elseif ($returnRow['joiningFeePlan'] == 3)
       {
            $commissiondate = strtotime ( '+3 month' ,strtotime($startDate)) ;
            $commissiondate = date("Y-m-d",$commissiondate);
       }       
       #Insert debit and commission start dates
       mysqli_query($this->conn,"UPDATE accountrecords SET adminFeeStartDate = '".mysqli_real_escape_string($this->conn,$startDate)."',subscriptionStartDate='".mysqli_real_escape_string($this->conn,$commissiondate)."' WHERE contractNo = '".mysqli_real_escape_string($this->conn,$contractNo)."';",MYSQLI_STORE_RESULT);
       if(mysqli_errno($this->conn) != 0)
       {
           #Failed to run query
           $xmlstr = <<<_XML_
<?xml version='1.0' standalone='yes'?>
 <returnCall>
  <status>Fail</status>                      
  <msg>Failed to execute query</msg>          
  <errorMsg>$this->conn->error<errorMsgmsg>             
 </returnCall>

_XML_;
           return $xmlstr;
           
       }
       
       
       #return success and commsion date
       $xmlstr = <<<_XML_
<?xml version='1.0' standalone='yes'?>
 <returnCall>
  <status>Success</status>                      
  <msg>All queries successfully executed</msg>          
  <commissionDate>$commissiondate</commissionDate>             
 </returnCall>

_XML_;
       return $xmlstr;
                  
       #Close prepared statement
       mysqli_close($this->conn);
       
   }
   
   ##############################adminPaymentAssignment###########################################
   function adminPaymentAssignmentFirstPayments()
   {
       #Get current month and year  
       $dateMonth = date('Y-m');
       
       $resultSet = mysqli_query($this->conn,"SELECT * FROM accountrecords WHERE firstSuccessfulPayDate like '".$dateMonth."%';",MYSQLI_STORE_RESULT);
       if(mysqli_errno($this->conn) != 0)
       {
           #Failed to run query
           $xmlstr = <<<_XML_
<?xml version='1.0' standalone='yes'?>
 <returnCall>
  <status>Fail</status>                      
  <msg>Failed to execute query</msg>          
  <errorMsg>$this->conn->error<errorMsgmsg>             
 </returnCall>

_XML_;
           return $xmlstr;
           
       }
       
       return $resultSet;
   }
   
   function adminPaymentPullFailedTransactionsByDate($contractNo,$dateToCheck)
   {
       #Validate
       if(!v::numeric()->validate($contractNo))#Failed validation
       {
           $xmlstr = <<<_XML_
<?xml version='1.0' standalone='yes'?>
 <returnCall>
  <status>Fail</status>                      
  <msg>Invalid contract number</msg>                      
 </returnCall>

_XML_;

         return $xmlstr;
         
       }
                     
       if(!v::date()->validate($dateToCheck))#Failed validation
       {           
           $xmlstr = <<<_XML_
<?xml version='1.0' standalone='yes'?>
 <returnCall>
  <status>Fail</status>                      
  <msg>Invalid date format</msg>                      
 </returnCall>

_XML_;

         return $xmlstr;          
         
       }   
       
       $resultSet = mysqli_query($this->conn,"SELECT count(*) FROM unpaidtransactionlog WHERE contractNo = '".mysqli_real_escape_string($this->conn,$contractNo)."' AND transactionDate like '".$dateToCheck."%';",MYSQLI_STORE_RESULT);
       if(mysqli_errno($this->conn) != 0)
       {
           #Failed to run query
           $xmlstr = <<<_XML_
<?xml version='1.0' standalone='yes'?>
 <returnCall>
  <status>Fail</status>                      
  <msg>Failed to execute query</msg>          
  <errorMsg>$this->conn->error<errorMsgmsg>             
 </returnCall>

_XML_;
           return $xmlstr;
           
       }
       
       return $resultSet;       
   }
   
   ##############################Heirarchy###########################################
   function hierarchyLevels($contractNo,$introducerNo)
   {                 
        #Validate contractNo
        if(!v::numeric()->validate($contractNo))#Failed validation
        {
        $xmlstr = <<<_XML_
<?xml version='1.0' standalone='yes'?>
<returnCall>
<status>Fail</status>                      
<msg>Invalid contract number</msg>                      
</returnCall>

_XML_;

        return $xmlstr;
        
      }
      
      #Validate introducerNo
        if(!v::numeric()->validate($introducerNo))#Failed validation
        {
        $xmlstr = <<<_XML_
<?xml version='1.0' standalone='yes'?>
<returnCall>
<status>Fail</status>                      
<msg>Invalid introducer number</msg>                      
</returnCall>

_XML_;

        return $xmlstr;
        
      }
      
      #All validated
      #Search if introducer is 0000000
      if($introducerNo == '0000000')
      {
          mysqli_query($this->conn,"INSERT INTO customerrelationship (contractNo,introducerNo,wealthCreatorLevel) VALUES ('".mysqli_real_escape_string($this->conn,$contractNo)."','".mysqli_real_escape_string($this->conn,$introducerNo)."','1');",MYSQLI_STORE_RESULT);
          if(mysqli_errno($this->conn) != 0)
          {
           #Failed to run query
           $xmlstr = <<<_XML_
<?xml version='1.0' standalone='yes'?>
 <returnCall>
  <status>Fail</status>                      
  <msg>Failed to execute query at inserting 0000000 relationship</msg>          
  <errorMsg>$this->conn->error<errorMsgmsg>             
 </returnCall>

_XML_;
             return $xmlstr;
             
          }
          
          #Success         
           $xmlstr = <<<_XML_
<?xml version='1.0' standalone='yes'?>
 <returnCall>
  <status>Success</status>                      
  <msg>Relationship set</msg>
 </returnCall>

_XML_;
           return $xmlstr;
           
      }else{
        #Build levels         
        mysqli_query($this->conn,"INSERT INTO customerrelationship (contractNo,introducerNo,wealthCreatorLevel) VALUES ('".mysqli_real_escape_string($this->conn,$contractNo)."','".mysqli_real_escape_string($this->conn,$introducerNo)."','1');",MYSQLI_STORE_RESULT);
        if(mysqli_errno($this->conn) != 0)
        {
         #Failed to run query
         $xmlstr = <<<_XML_
<?xml version='1.0' standalone='yes'?>
<returnCall>
<status>Fail</status>                      
<msg>Failed to execute query at inserting 0000000 relationship</msg>          
<errorMsg>$this->conn->error<errorMsgmsg>             
</returnCall>

_XML_;
           return $xmlstr;
           
        }
        return "pass";
        #Loop through the heirarchies
        $counter = 2;
        while(heirarchyLevelBuilder($contractNo,$introducerNo,$counter))
        {
            $counter++;
        }
      }
      
      //Build levels
      $resultSet = mysqli_query($this->conn,"SELECT * FROM customerrelationship WHERE contractNo = '".mysqli_real_escape_string($this->conn,$contractNo)."';",MYSQLI_STORE_RESULT);
      while($returnRow = mysqli_fetch_assoc($resultSet))
      {
          mysqli_query($this->conn,"UPDATE userhierarchy SET totalUsers = (totalUsers+1),wc".$returnRow['wealthCreatorLevel']."=(wc".$returnRow['wealthCreatorLevel']."+1) WHERE contractNo=".$returnRow['introducerNo'].";",MYSQLI_STORE_RESULT);
      }
   }
   
   function heirarchyLevelBuilder($contractNo,$introducerNo,$counter)
   {       
        if($counter > 7)
        {
            $counter = 7;
        }

        if($this->returnIntroducerNo == 0)
        {

        }else{
            $introducerNo = $this->returnIntroducerNo;
        }
       
        #If looping for first time
        $resultSet = mysqli_query($this->conn,"SELECT introducerNo FROM customer WHERE contractNo = '".mysqli_real_escape_string($this->conn,$introducerNo)."';",MYSQLI_STORE_RESULT);
        if(mysqli_errno($this->conn) != 0)
        {
        #Failed to run query
        $xmlstr = <<<_XML_
<?xml version='1.0' standalone='yes'?>
<returnCall>
<status>Fail</status>                      
<msg>Failed to execute levelBuilder query</msg>          
<errorMsg>$this->conn->error<errorMsgmsg>             
</returnCall>

_XML_;
            return $xmlstr;
            
         }

         $returnRow = mysqli_fetch_assoc($resultSet); 

        mysqli_query($this->conn,"INSERT INTO customerrelationship (contractNo,introducerNo,wealthCreatorLevel) VALUES ('".mysqli_real_escape_string($this->conn,$contractNo)."','".mysqli_real_escape_string($this->conn,$returnRow[0])."','".$counter."');",MYSQLI_STORE_RESULT);
        if(mysqli_errno($this->conn) != 0)
        {
      #Failed to run query
      $xmlstr = <<<_XML_
<?xml version='1.0' standalone='yes'?>
<returnCall>
<status>Fail</status>                      
<msg>Failed to execute query at inserting 0000000 relationship</msg>          
<errorMsg>$this->conn->error<errorMsgmsg>             
</returnCall>

_XML_;
            return $xmlstr;
            
         }
     
        if($returnRow[0] == '0000000')
        {
             
        }else{
           $this->returnIntroducerNo = $returnRow[0];
        }               
   }
   
   function heirarchyDuplicateRemover()
   {
        $resultSet = mysqli_query($this->conn,"SELECT `contractNo`,`introducerNo`,COUNT(*) AS c FROM customerrelationship GROUP BY `contractNo`,`introducerNo` HAVING c > 1;",MYSQLI_STORE_RESULT);
        if(mysqli_errno($this->conn) != 0)
        {
        #Failed to run query
        $xmlstr = <<<_XML_
<?xml version='1.0' standalone='yes'?>
<returnCall>
<status>Fail</status>                      
<msg>Failed to execute heirarchyDuplicateRemover query</msg>          
<errorMsg>$this->conn->error<errorMsgmsg>             
</returnCall>

_XML_;
            return $xmlstr;
            
         }
         $loopCounter = 0;
         while($returnRow = mysqli_fetch_assoc($resultSet))
         {
             $loopCounter = $returnRow[2];
             $loopCounter = $loopCounter - 1;
                          
             for($x = 0;$x < $loopCounter;$x++)
             {
               #Get last id
               $resultDeleteSet = mysqli_query($this->conn,"SELECT `relationshipID` FROM customerrelationship WHERE contractNo = '".$returnRow[0]."' ORDER BY relationshipID DESC LIMIT 1;",MYSQLI_STORE_RESULT);
               $returnDeleteRow = mysqli_fetch_assoc($resultDeleteSet);                
               mysqli_query($this->conn,"DELETE FROM customerrelationship WHERE relationshipID = '".$returnDeleteRow[0]."';",MYSQLI_STORE_RESULT);
             }
         }
   }
   
   ##############################Creator bonus###########################################
   function creatorBonusQualifyingFlag($contractNo)
   {
       #Get this guys numbers
       $resultSet = mysqli_query($this->conn,"SELECT wc1,wc2,wc3,wc4,wc5,wc6,wc7 FROM failedpaymentsuserhierarchy WHERE `contractNo` = '".mysqli_real_escape_string($this->conn,$contractNo)."';",MYSQLI_STORE_RESULT);
       if(mysqli_errno($this->conn) != 0)
       {
        #Failed to run query
        $xmlstr = <<<_XML_
<?xml version='1.0' standalone='yes'?>
<returnCall>
<status>Fail</status>                      
<msg>Failed to execute creatorBonusQualifyingFlag query</msg>          
<errorMsg>$this->conn->error<errorMsgmsg>             
</returnCall>

_XML_;
            return $xmlstr;
            
        }
        
        //$returnRow = mysqli_fetch_assoc($resultSet);
        $returnRow = mysqli_fetch_row($resultSet);
        
        #Get the rules
        $resultSetBonusLevel = mysqli_query($this->conn,"SELECT * FROM creatorbonus ORDER BY bonusLevel DESC;",MYSQLI_STORE_RESULT);
       if(mysqli_errno($this->conn) != 0)
       {
        #Failed to run query
        $xmlstr = <<<_XML_
<?xml version='1.0' standalone='yes'?>
<returnCall>
<status>Fail</status>                      
<msg>Failed to execute creatorBonusQualifyingFlag query</msg>          
<errorMsg>$this->conn->error<errorMsgmsg>             
</returnCall>

_XML_;
            return $xmlstr;
            
        }
        
        $bonusLevelFound = 0;
        
        while($returnRowBonusLevel = mysqli_fetch_assoc($resultSetBonusLevel))
        {
            $total = 0;            
            for($x=0; $x < $returnRowBonusLevel['WCQMaxLevel'];$x++)
            {
                $total += $returnRow[$x];
            }
           
           if($returnRowBonusLevel['qualifyingUsers'] <= $total)
           {
               #We flag that we found a relevant level
               $bonusLevelFound = $returnRowBonusLevel['bonusLevel'];
               break;
           }
        }
        
        #Failed to run query
        $xmlstr = <<<_XML_
<?xml version='1.0' standalone='yes'?>
<returnCall>
<status>Success</status>                      
<msg>Function successfully completed</msg>          
<bonusLevel>$bonusLevelFound</bonusLevel>             
</returnCall>

_XML_;
            //return;
            return $xmlstr;                
   }
   
   ##############################Gross Subscriber Fee###########################################
   function grossSubscriberFee($contractNo,$checkStartDate,$checkDate)
   {
       //Get user details
        #Validate
       if(!v::numeric()->validate($contractNo))#Failed validation
       {
           $xmlstr = <<<_XML_
<?xml version='1.0' standalone='yes'?>
 <returnCall>
  <status>Fail</status>                      
  <msg>Invalid contract number</msg>                      
 </returnCall>

_XML_;

         return $xmlstr;
         
       }
	
       if(!v::date()->validate($checkStartDate))#Failed validation
       {           
           $xmlstr = <<<_XML_
<?xml version='1.0' standalone='yes'?>
 <returnCall>
  <status>Fail</status>                      
  <msg>Invalid date format</msg>                      
 </returnCall>

_XML_;

         return $xmlstr;          
         
       }
       
       if(!v::date()->validate($checkDate))#Failed validation
       {           
           $xmlstr = <<<_XML_
<?xml version='1.0' standalone='yes'?>
 <returnCall>
  <status>Fail</status>                      
  <msg>Invalid date format</msg>                      
 </returnCall>

_XML_;

         return $xmlstr;          
         
       }              
              
       #Get contract details                       
       $resultSet = mysqli_query($this->conn,"SELECT * FROM accountrecords WHERE contractNo = '".mysqli_real_escape_string($this->conn,$contractNo)."';",MYSQLI_STORE_RESULT);
       if(mysqli_errno($this->conn) != 0)
       {
           #Failed to run query
           $xmlstr = <<<_XML_
<?xml version='1.0' standalone='yes'?>
 <returnCall>
  <status>Fail</status>                      
  <msg>Failed to execute query</msg>          
  <errorMsg>$this->conn->error<errorMsgmsg>             
 </returnCall>

_XML_;
           return $xmlstr;
           
       }
       
       //$row_cnt = mysqli_num_rows($resultSet);
       //file_put_contents('log.log', print_r($resultSet));
       $row_cnt = mysqli_num_rows($resultSet);
       
       if($row_cnt === 0)
       {
           $xmlstr = <<<_XML_
<?xml version='1.0' standalone='yes'?>
 <returnCall>
  <status>Fail</status>                      
  <msg>Contract does not exist</msg>                        
 </returnCall>

_XML_;
           return $xmlstr;
           
       }
       
       $returnContractRow = mysqli_fetch_assoc($resultSet);
       
       //Check that the date passed is greater or equal to the subscriptionStartDate       
       $date1 = new DateTime($checkDate);
       $date2 = $returnContractRow['subscriptionStartDate'];    
       
       if ($date1 >= $date2)
       {
           //Pass, now check if all admin fees paid
           $resultPaymentSet = mysqli_query($this->conn,"SELECT sum(amount) as total FROM payments WHERE paymentDate < '".substr($returnContractRow['subscriptionStartDate'],0,7)."';",MYSQLI_STORE_RESULT);
           if(mysqli_errno($this->conn) != 0)
           {
               #Failed to run query
               $xmlstr = <<<_XML_
<?xml version='1.0' standalone='yes'?>
 <returnCall>
  <status>Fail</status>                      
  <msg>Failed to execute query summing of payments</msg>          
  <errorMsg>$this->conn->error<errorMsgmsg>             
 </returnCall>

_XML_;
                return $xmlstr;
                
            }
	    
            $returnPaymentRow = mysqli_fetch_assoc($resultPaymentSet);
            
            if($returnPaymentRow['total'] >= $returnContractRow['adminFeePayable'])
            {                
                //Admin fee has been fully paid
                //Check that subscription amount was paid for this month
                $subscriptionFee = $returnContractRow['subscriptionBalance']/$returnContractRow['noInstalmentsBalance'];
                
		$resultPaymentSubscriptionMonth = mysqli_query($this->conn,"SELECT sum(amount) as total FROM payments WHERE contractNo = '".mysqli_real_escape_string($this->conn,$contractNo)."' AND paymentDate >= '".$checkStartDate."' AND paymentDate <= '".$checkDate."';",MYSQLI_STORE_RESULT);
		//$resultPaymentSubscriptionMonth = mysqli_query($this->conn, "SELECT sum(amount) as total FROM payments WHERE paymentDate <= '".substr($returnContractRow['subscriptionStartDate'],0,7)."' AND paymentDate <= '".mysqli_real_escape_string($this->conn,$checkStartDate)."';",MYSQLI_STORE_RESULT);
                $returnPaymentSubscriptionMonthRow = mysqli_fetch_assoc($resultPaymentSubscriptionMonth);
                $totalPaidThisMonth = $returnPaymentSubscriptionMonthRow['total'];
		
                if($returnPaymentSubscriptionMonthRow['total'] >= $subscriptionFee)
                {                                        
                    //This contract has paid a figure greate or equal to their monthly subscription
                    $xmlstr = <<<_XML_
<?xml version='1.0' standalone='yes'?>
 <returnCall>
  <status>Success</status>                      
  <grossSubFee>$subscriptionFee</grossSubFee>                        
  <totalPaidThisMonth>$totalPaidThisMonth</totalPaidThisMonth>                        
  <reason>Subscription fee paid</reason>                        
 </returnCall>

_XML_;
                    return $xmlstr;
                    
                }else{
                     //This contract has paid a figure less than their monthly subscription fee
                    $xmlstr = <<<_XML_
<?xml version='1.0' standalone='yes'?>
 <returnCall>
  <status>Success</status>                      
  <grossSubFee>0</grossSubFee>                        
  <totalPaidThisMonth>$totalPaidThisMonth</totalPaidThisMonth>                        
  <reason>Subscription fee NOT fully paid</reason>                        
 </returnCall>

_XML_;
                    return $xmlstr;
                    
                }
            }else{                
                //Fail           
                $xmlstr = <<<_XML_
<?xml version='1.0' standalone='yes'?>
 <returnCall>
  <status>Success</status>                      
  <grossSubFee>0</grossSubFee>                        
  <reason>Admin fee not fully paid</reason>                        
 </returnCall>

_XML_;
                return $xmlstr;
                
            }
       }else{
           //Fail           
           $xmlstr = <<<_XML_
<?xml version='1.0' standalone='yes'?>
 <returnCall>
  <status>Success</status>                      
  <grossSubFee>0</grossSubFee>                        
  <reason>Date parsed less than subscriptionStartDate</reason>                        
 </returnCall>

_XML_;
            return $xmlstr;
            
           
       }
   }
}

$dbObject = new db_object();


   
