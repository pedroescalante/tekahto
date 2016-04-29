@extends('layouts.master')

<?php
    $cc_status  = ['Unknown', 'Error', 'Deleted', 'OK', 'Inactive'];
    $in_status  = [0 => 'Unpaid', 1 => 'Paid'];
    $auto_charge = [0=>'No', 1=>'Yes'];
?>

@section('content')
        <div class="col-sm-12">
            <div class="panel panel-primary">
                <div class="panel-heading"> Contact Info </div>
                <div class="panel-body">
                    <table class="table table-condensed table-bordered">
                        <tr>
                            <th> ID </th>
                            <td> {{ $contact['Id'] }} </td>
                        </tr>
                        <tr>
                            <th> First Name </th>
                            <td> {{ $contact['FirstName'] }} </td>
                        </tr>
                        <tr>
                            <th> Last Name </th>
                            <td> <?php if( isset($contact['LastName']) ) echo $contact['LastName']; ?> </td>
                        </tr>
                        <tr>
                            <th> Registered Credit Cards </th>
                            <td> {{ count($contact['CreditCards']) }} </td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="panel panel-primary">
                <div class="panel-heading"> Credit Cards </div>
                <div class="panel-body">
                    <table class="table table-condensed table-bordered">
                        <tr class="info">
                            <th> Id </th>
                            <th> Last 4 Numbers </th>
                            <th> Type </th>
                            <th> Status </th>
                        </tr>
                        @foreach($contact['CreditCards'] as $card)
                        <tr>
                            <td> {{ $card['Id'] }} </td>
                            <td> {{ $card['Last4'] }} </td>
                            <td> {{ $card['CardType'] }} </td>
                            <td> {{ $cc_status[$card['Status']] }} ({{ $card['Status'] }}) </td>
                        </tr>
                        @endforeach
                    </table>
                </div>
            </div>

            <div class="panel panel-primary">
                <div class="panel-heading"> Subscriptions </div>
                <div class="panel-body">
		    <table class="table table-condensed">
			<tr>
			    <th> ID </th>
			    <th> Merchant ID </th>
			    <th> Product ID </th>
			    <th> Start Date </th>
			    <th> AutoBill </th>
			    <th> Status </th>
			<tr>
                @foreach($contact['subscriptions'] as $sub)
			<tr>
                	    <td> <?php if( isset($sub['Id']))  		     echo $sub['Id'] ?> </td>
                    	    <td> <?php if( isset($sub['merchantAccountId'])) echo $sub['merchantAccountId'] ?> </td>
                    	    <td> <?php if( isset($sub['ProductName'])) 	     echo $sub['ProductName'] ?> </td>
                	    <td> <?php if( isset($sub['StartDate']))         echo $sub['StartDate']->format('Y-m-d') ?> </td>
                	    <td> <?php if( isset($sub['AutoCharge']))        echo $auto_charge[$sub['AutoCharge']]; else echo "-"; ?> </td> 
                	    <td> <?php if( isset($sub['Status'])){
					if( $sub['Status']=="Active") 
					    echo "<span class='label label-success'>".$sub['Status']."</span>";
					else
					    echo $sub['Status'];
				       }
				 ?> </td>
			</tr>
                @endforeach
		    </table>
                </div>
            </div>
        
            <div class="panel panel-primary">
                <div class="panel-heading"> Jobs </div>
                <div class="panel-body">
                    @foreach($contact['Jobs'] as $job)
                    <div>Job Id: <b> {{$job['Id']}} </b> </th></div>
                    <div> Job Title <b> {{$job['JobTitle']}} </b> </div>
                    <div> Product Id <b> {{$job['ProductId']}} </b> </div>
            
                    <h5><b>Invoices</b></h5>
                    <table class="table table-condensed table-bordered">
                        <tr class="info">
                            <th> Id   </th>
                            <th> Description  </th>
                            <th> Type         </th>
                            <th> PayStatus    </th>
                            <th> Total        </th>
                            <th> Due          </th>
                            <th> Paid         </th>
                        </tr>
                        @foreach($job['invoices'] as $invoice)
                        <tr>
                            <td> {{$invoice['Id']}}             </td>
                            <td> {{$invoice['Description']}}    </td>
                            <td> <?php if( isset($invoice['InvoiceType']) )  echo $invoice['InvoiceType']; ?>    </td>
                            <td> {{ $in_status[$invoice['PayStatus']] }} </td>
                            <td> <?php if(isset($invoice['InvoiceTotal'])) echo $invoice['InvoiceTotal']; else echo "-" ?>   </td>
                            <td> {{$invoice['TotalDue']}}       </td>
                            <td> {{$invoice['TotalPaid']}}      </td>
                        </tr>
                        @endforeach
                    </table>
                    <hr>
                    @endforeach
                </div>
            </div>
        </div>
@stop      
