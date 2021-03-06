<?php

namespace contact;

use src\config\connection as dbconnect;
use src\config as config;
use src\config\responses as res;
use src\validations\contact as validate;

class apiClass {

    //function get contact details of user
    public function getContactDetails($id) {
        $jwt = new config\jwt();

        //get the object of database connection
        $dbobj = new dbconnect\dbconnection();
        $fm = $dbobj->connect();

        //object from response class
        $res = new res\userResponses();
   
        
        //fetch contact details.
        $findCommand = $fm->newFindCommand('InsertUserContact_CONTACT');

        //specify the email and password match criteria
        $findCommand->addFindCriterion('_kf_Id_n', ' == '.$id);


        $contact;

        //execute the above command
        $contactResult = $findCommand->execute(); 

        

        //checking for errors in the result
        if (\FileMaker::isError($contactResult)) {
            if( $contactResult->getMessage() == "No records match the request" ) {
                return NULL;
            }
            $contactResultError = 'Find Error: '. $contactResult->getMessage(). ' (' . $contactResult->code. ')';
            return $contactResultError;
            
        }
        return $contactResult->getRecords();
        
    }

    //function to update or create contact of user
    public function updateContact(  $request,  $response) {

        //connection class object
        $dbobj = new dbconnect\dbconnection();

        //connection to get the filemaker connection object
        $fm = $dbobj->connect();

        //object from response class
        $res = new res\contactResponses();

        //validating class object
        $valid = new validate\validate();

        //get the request body
        $vars = json_decode($request->getBody());
        
        $ret = new \stdClass();


        //validating the request header data
        $return = $valid->valContactData($vars, $request, $response);
        if($return != "no error") {
            $ret->success = "false";
            $ret->message = $return;
            return json_encode ( $ret ); 
        }
        else {
            $ret->success = "true";
           
        
            $findCommand = $fm->newFindCommand('insertUserContact_CONTACT');
            $findCommand->addFindCriterion('_kf_Id_n','=='.$vars->id);
            $result = $findCommand->execute();
            $values = $res->updateResponse($vars);
            //checking for error
            if (\FileMaker::isError($result)) {
               // echo $result->getMessage(); 
                if( $result->getMessage() == "No records match the request" ) {
                   // echo "I am here";
                    //print_r($values);


                     //populating fields of student layout
                    $rec = $fm->createRecord('insertUserContact_CONTACT', $values);
                    $insertResult = $rec->commit();

                    //checking error populating fields in insertUserContact_CONTACT layout
                    if (\FileMaker::isError($insertResult)) {
                        $findError = 'Find Error: '. $insertResult->getMessage(). ' (' . $insertResult->code. ')';
                        $ret->success = "false";
                        $ret->message = $findError;
                        return json_encode( $ret );
                    }

                    $findCommand = $fm->newFindCommand('insertUserContact_CONTACT');
                    $findCommand->addFindCriterion('_kf_Id_n','=='.$vars->id);
                    $result = $findCommand->execute();


                    if (\FileMaker::isError($result)) {
                        $findError = 'Find Error: '. $result->getMessage(). ' (' . $result->code. ')';
                        $ret->success = "false";
                        $ret->message = $findError;
                        return json_encode( $ret );
                    }

                    //returning success response
                   // echo "insert success";
                }
                else {
                    $findError = $result->getMessage(). ' (' . $result->code. ')';
                    $ret->success = "false";
                    $ret->message = $findError;
                    return json_encode ( $ret ); 
                }
            }
           // print_r($result);
            $temp = $result->getRecords();
            // exit(0);
            //getting the specific record Id
            $record = $temp[0]->_impl;
            $rec = $record->getRecordId();
           // print_r($record);


            $newEdit = $fm->newEditCommand('insertUserContact_CONTACT', $rec, $values);
            $result = $newEdit->execute(); 

            //checking for any error
            if (\FileMaker::isError($result)) {
                $findError = $result->getMessage(). ' (' . $result->code. ')';
                $ret->success = "false";
                $ret->message = $findError;
                return json_encode( $ret ); 
            }

            return json_encode($ret);
        }

    } 

    // function format the records from the conatct portal
    function getContactFromPortal( $portalRecods ) {

        $portalData = [];
        $val = $portalRecods[0]->_impl->_fields;
        array_push ( $portalData , array(
            'state' => $val['user_CONTACT_id::State_t'][0],
            'streetName' => $val['user_CONTACT_id::StreetName_t'][0],
            'district' => $val['user_CONTACT_id::District_t'][0],
            'pin' => $val['user_CONTACT_id::Pin_n'][0], 
            'phone' => $val['user_CONTACT_id::_ku_Phone_n'][0]
        ));
    

        return $portalData[0];
    }
    
}