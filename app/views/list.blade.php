@extends('layouts.master')

@section('content')
        <div class="col-sm-12 list">
            <h4>Contacts</h4>
            <table class="table table-striped">
                <tr>
                    <th> ID                 </th>
                    <th> Email              </th>
                    <th> Name               </th>
                    <th> Subscription Plans </th>
                </tr>
                @foreach($contacts as $contact)
                <tr>
                    <td> <?php if( isset($contact['Id'])) echo $contact['Id'] ?> </td>
                    <td> <?php if( isset($contact['Email'])) echo $contact['Email'] ?> </td>
                    <td> <?php if( isset($contact['FirstName'])) echo $contact['FirstName']; echo " "; 
                               if( isset($contact['LastName']))  echo $contact['LastName'] ?> </td>
                    <td>
		    <?php if(isset($contact['subscriptions'])) { ?>
                    @foreach($contact['subscriptions'] as $sub)                       
			<?php if( in_array($sub['ProductId'], ['216', '220', '218', '186', '252']) ){ ?>
                        <li>
				<?php echo $sub['ProductName']?>
                            <?php if( isset($sub['Status']) ){
                                if( $sub['Status']=="Active") 
                                    echo "<span class='label label-success'> (".$sub['Status'].") </span>";
                                else
                                    echo " (".$sub['Status'].")";
                                }
                            ?>
                        </li>
			<?php } ?>
		    @endforeach
		    <?php } ?>
                    </td>
                </tr>
                @endforeach
            </table>
        </div>
@stop      
