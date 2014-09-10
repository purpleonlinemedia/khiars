<?php
    include './index.php';
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$dbObject = new db_object();

$dbObject->paymentDates('0000001','2014-03-15');

print "<br>here";