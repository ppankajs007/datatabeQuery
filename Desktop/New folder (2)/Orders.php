<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
require APPPATH . 'third_party/ClickSend/vendor/autoload.php';
require_once APPPATH . 'third_party/PHPExcel/spout-2.4.3/src/Spout/Autoloader/autoload.php';
use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Common\Type;

class Orders extends MX_Controller
{
    function __construct()
    {
        parent::__construct();

        $this->load->helper(array('form', 'url'));
        $this->load->library('form_validation');
        $this->load->library('security');
        $this->load->library('tank_auth');
        $this->lang->load('tank_auth');
        $this->load->module('layouts');
        $this->load->library('template');
        $this->load->model('order_model');
        $this->load->model('user_model');
        if (!$this->tank_auth->is_logged_in()) {
            redirect('auth/login');
        }
    }

    function index()
    {   
        $data = array();
        $result = $this->db->select('*')->from('pk_order')->get();
        $data['order'] = $result->result('array');  
        $this->load->view('index', $data);
        $this->template->title('order');
        $this->template
                ->set_layout('inner')
                ->build('orders', $data);
    }

    function add()
    {
        if( $this->input->post() ){
            $post_data = array(
                    'status'                  => $this->input->post('status'),
                    'sales_primary'           => $this->input->post('sales_primary'),
                    'sales_secondary'         => $this->input->post('sales_secondary'),
                    'vendor'                  => $this->input->post('vendor'),
                    'vendor_invoice'          => $this->input->post('vendor_invoice'),
                    //'product_type'            => $this->input->post('product_type'),
                    'is_pickup'               => $this->input->post('is_pickup'),
                    'resale_certificate'      => $this->input->post('resale_certificate'),
                    'total'                   => $this->input->post('total'),
                    'total_due'               => $this->input->post('total_due'),
                    'paid'                    => $this->input->post('paid'),
                    'refunded'                => $this->input->post('refunded'),
                    'first_name'              => $this->input->post('first_name'),
                    'last_name'               => $this->input->post('last_name'),
                    'requested_delivery_date' => $this->input->post('requested_delivery_date'),
                    'requested_date_notes'    => $this->input->post('requested_date_notes'),
                    'hard_date'               => $this->input->post('hard_date'),
                    'estimated_delivery_date' => $this->input->post('estimated_delivery_date'),
                    'estimated_date_notes'    => $this->input->post('estimated_date_notes'),
                    'schedule_delivery_date'  => $this->input->post('schedule_delivery_date'),
                    'show_delivery_details'   => $this->input->post('show_delivery_details'),
                    'survey'                  => $this->input->post('survey'),
                    'survey_date'             => $this->input->post('survey_date'),
                    'customer_id'             => $this->input->post('customer_id'),
                    'modify_date'             => date('Y-m-d H:i:s'),
                    'created'                 => date('Y-m-d H:i:s'),
                );
            if( !$order_id = App::save_data( 'pk_order', $post_data ) )
            {   
                
                $this->session->set_flashdata('response_status', 'error');
                $this->session->set_flashdata('message', 'Order didn\'t save, Try again!');
                redirect('/orders');
            }
            else 
            {   
                //save order logs
                log_orders( 
                    $order_id, 
                    $this->session->userdata('user_id'), 
                    'files(orders)' 
                );
                
                $this->session->set_flashdata('response_status', 'success');
                $this->session->set_flashdata('message', 'Order has been added successfully');
                redirect('/orders');
            }
            
        }
        $data = array();
        $userQuery = $this->db->select('*')->from('vendor')->get();
        $data['users'] = $userQuery->result('array');
        $this->load->view('index', $data);
        $this->template->title('Order');
        $this->template
                ->set_layout('inner')
                ->build('add_order', $data);
    }

    /*function edit($id){
        $data = array();
        $id = $this->uri->segment(3);

        if( $this->input->post() ){
            $post_data = array(
                    'is_customer'             => $this->input->post('is_customer'),
                    'first_name'              => $this->input->post('first_name'),
                    'last_name'               => $this->input->post('last_name'),
                    'status'                  => $this->input->post('status'),
                    'vendor'                  => $this->input->post('vendor'),
                    'resale_certificate'      => $this->input->post('resale_certificate'),
                    'vendor_invoice'          => $this->input->post('vendor_invoice'),
                    'requested_delivery_date' => $this->input->post('requested_delivery_date'),
                    'notes'                   => $this->input->post('notes'),
                    'hard_date'               => $this->input->post('hard_date'),
                    'estimated_delivery_date' => $this->input->post('estimated_delivery_date'),
                    'schedule_delivery_date'  => $this->input->post('schedule_delivery_date'),
                    'show_delivery_details'   => $this->input->post('show_delivery_details'),
                    'survey'                  => $this->input->post('survey'),
                    'survey_date'             => $this->input->post('survey_date'),
                    'modify_date'             => date('Y-m-d H:i:s'),
                );
            if( !$order_id = App::update( 'pk_order',array('id' => $id),$post_data ) )
            {
                $this->session->set_flashdata('response_status', 'error');
                $this->session->set_flashdata('message', 'Order didn\'t save, Try again!');
                redirect('/order/edit/'.$id);
            }
            else
            {       
                redirect('/order/edit/'.$id);
            }
            
        }
        $userQuery = $this->db->select('*')->from('vendor')->get();
        $data['users'] = $userQuery->result('array');
        $data['edit_order'] = $this->order_model->findWhere( 'pk_order', array( 'id' => $id ), FALSE, array( '*' ) );
        $this->load->view('index', $data);
        $this->template->title('Order');
        $this->template
                ->set_layout('inner')
                ->build('edit', $data);

    }*/
    
    function appendcat() {
      if( isset( $_POST['catId'] ) && !empty( $_POST['catId'] ) ) {
          $data = $this->order_model
                       ->findWhere('category',array('cat_parent' => $_POST['catId']),true,array('*'));
          if (!empty( $data ) && $data == true ) {
            foreach ($data as $key => $value) {
                    $catData[$value['id']] = array("id" => $value['id'],'cat_name' => $value['cat_name'] );
            }
                echo json_encode($catData);
            }else {
              echo "false";  
                }
            }else{
          echo "false";
      }

    }

    

    function product($id) {
        $data = array();
        $id = $this->uri->segment(3);
        if( $this->input->post() ){
            /*pr($_POST,1);*/

            $post_data = array(
                            'vendor'                     => $this->input->post('vendor'),
                            'requested_delivery_date'    => $this->input->post('requested_delivery_date'),  
                            'requested_date_notes'       => $this->input->post('requested_notes'),          
                            'hard_date'                  => $this->input->post('hard_date'),
                            'resale_certificate'         => $this->input->post('resale_certificate'),
                            'is_pickup'                  => $this->input->post('is_pickup'),
                            'is_locked'                  => $this->input->post('is_locked'),
                            'payment_person'             => $this->input->post('payment_person'),
                            'payment_person_name'        => $this->input->post('payment_person_name'),
                            'has_a_uez'                  => $this->input->post('has_a_uez'),
                            'has_a_stform'               => $this->input->post('has_a_stform'),
                            'st8_nj_hyperlink'           => $this->input->post('st8_nj_hyperlink'),
                            'ST124_hyperlink'            => $this->input->post('ST124_hyperlink'),
                            'Is_Colourtone'              => $this->input->post('Is_Colourtone'),
                            'ordering_note'              => $this->input->post('ordering_note'),
                            'assembly_note'              => $this->input->post('assembly_note'),
                            'installation_note'          => $this->input->post('installation_note'),
                            'delivery_note'              => $this->input->post('delivery_note'),
                            'Is_Contract'                => $this->input->post('Is_Contract'),
                            'discount'                   => $this->input->post('discount'),
                            'discount_per'               => $this->input->post('discount_per'),
                            'total'                      => $this->input->post('total'),
                            'tax'                        => $this->input->post('tax'),
                            'delivery_price'             => $this->input->post('delivery_price'),
                            'cost_t_price'               => $this->input->post('cost_t_price'),
                            'subtotal'                   => $this->input->post('subtotal')
                );
                

            $old_data = (array) App::get_row_by_where('pk_order', array( 'id' => $id ) );
            $product_data = array();

            $order_id['pro_field'] = App::update( 'pk_order',array('id' => $id),$post_data );
            
            /*var_dump($this->input->post('tax'));
            echo $this->db->last_query();die;*/
            
            $sumTotal = 0;
            if( !empty( $_POST['product'] ) ){

                $findData =  $this->order_model->countNumrows( 'product_order', array( 'order_id' => $id ) );
                if( $findData == TRUE  ){
                    $this->order_model->deleteWhere( 'product_order',array( 'order_id' => $id ) );
                }
                
                $findDatasub =  $this->order_model->countNumrows( 'product_order_child', array( 'order_id' => $id ) );
                if( $findDatasub == TRUE  ){
                    $this->order_model->deleteWhere( 'product_order_child',array( 'order_id' => $id ) );
                }

                $findDatasubchild =  $this->order_model->countNumrows( 'product_order_subchild', array( 'order_id' => $id ) );
                if( $findDatasubchild == TRUE  ){
                    $this->order_model->deleteWhere( 'product_order_subchild',array( 'order_id' => $id ) );
                }                
                
                foreach ($_POST['product'] as $key => $value) {

                    /*pr( $value );continue;*/
                                        
                        $sql = "INSERT INTO product_order ( order_id, product_id,Item_code, style_id,description,descriptionII, qty,price,u_price) 
                                    VALUES ( '". $id ."', '". $value['product_id'] ."', '". $value['item_code'] ."', '". $value['style_id'] ."',
                                    '". $value['description'] ."', '". $value['descriptionII'] ."', '". $value['qty'] ."', '". $value['price'] ."',
                                     '". $value['u_price'] ."')";
                        $query['data'] = $this->db->query( $sql );
                        $parent_id_i = $this->db->insert_id();
                        
                        if( !empty( $value['aftertotal'] ) ){
                        $sumTotal += $value['aftertotal'];
                        }
                    
                    
                    if( isset($value['child_item']) ){
                        foreach ($value['child_item'] as $key => $values) {

                            if( !empty($values['product_id'] ) ){

                                $sqlsub = "INSERT INTO product_order_child ( order_id, pro_order_id, qty, item, style, description,descriptionII, price,u_price,product_id,pro_parent_id) 
                                            VALUES ( '". $id ."', '". $parent_id_i ."', '". $values['qty'] ."', '". $values['Item_code'] ."',
                                            '". $values['style_id'] ."', '". $values['description'] ."', '". $values['descriptionII'] ."', '". $values['price'] ."', '". $values['u_price'] ."', '". $values['product_id'] ."', '". $value['product_id'] ."')";
                                $querysub['data'] = $this->db->query( $sqlsub );
                                $parent_sub_id_i = $this->db->insert_id();

                                }
                                
                                if( isset($values['sub_child']) ){
                                    foreach ($values['sub_child'] as $key => $subvalues) {
                                        if( !empty($subvalues['product_id'] ) ){
                                        //pr( $subvalues );continue;

                                            $sqlsubchild = "INSERT INTO product_order_subchild ( order_id, pro_order_id, qty, item, style, description,descriptionII, price,u_price,product_id,pro_parent_id) 
                                                        VALUES ( '". $id ."', '". $parent_sub_id_i ."', '". $subvalues['qty'] ."', '". $subvalues['Item_code'] ."',
                                                        '". $subvalues['style_id'] ."', '". $subvalues['description'] ."', '". $subvalues['descriptionII'] ."', '". $subvalues['price'] ."', '". $subvalues['u_price'] ."', '". $subvalues['product_id'] ."', '". $values['product_id'] ."')";
                                            $querysubchild['data'] = $this->db->query( $sqlsubchild );
                                        }
                                    }
                                }    
                        }
                    }
                }
                //$this->order_model->updateWhere('pk_order',array( 'id' => $id), array( 'total' => $sumTotal ) );
            }  else {
                $this->order_model->deleteWhere( 'product_order',array( 'order_id' => $id ) );
            }
            

            if( ($query['data'] == TRUE) || ($order_id['pro_field'] == TRUE) ){
                
               //saving order logs
               /* log_orders( 
                    $id, 
                    $this->session->userdata('user_id'), 
                    'product(orders)' 
                );*/

                $this->session->set_flashdata('response_status', 'success');
                $this->session->set_flashdata('message', 'Order updated successfully.');
            }
            redirect( 'orders/product/'.$id );      
        }
        

        $data['edit_order'] = $this->order_model->findWhere( 'pk_order', array( 'id' => $id ), FALSE, array( '*' ) );
    
        
        $data['vendor_pro'] = $this->order_model->findWhere( 'pk_product', array( 'vendor_id' => $data['edit_order']['vendor'] ), TRUE, array( '*' ) );
        
        $userQuery = $this->db->select('*')->from('users')
                        ->join('user_profiles', 'users.id = user_profiles.user_id', 'FULL OUTER JOIN')->get();
        $data['users'] = $userQuery->result('array');
        
        
        $userQuery = $this->db->select('*')->from('vendor')->get();
        $data['vendor_users'] = $userQuery->result('array');
        
        $styleQuery = $this->db->select('*')->from('style')->get();
        $data['style'] = $styleQuery->result('array');
        $data['vendors'] = (array) App::get_by_where('vendor', array( '1' => 1 ) );
        $data['styles'] = (array) App::get_by_where('style', array( 'vendor_id' =>  $data['edit_order']['vendor']) );
        $data['category'] = (array) App::get_by_where('category', array( '1' => 1 ) );
        
        $sql = "SELECT p_o.*,p_o.id as p_oid,p_o.style_id as styId,pk.Item_Code as pkIC, pk.*,pk.id as pkid,pk.Item_description as pkID,s.*,s.style_name as sn,s.style_code as sc FROM product_order p_o LEFT JOIN pk_product pk on pk.id = p_o.product_id LEFT JOIN style s on s.id = pk.style_id WHERE order_id = '$id'";
        $data['product_order'] = $this->order_model->custom_query($sql,false,'array');
        

        $sqldata = "SELECT p_o_c.*,p_o_c.id as p_o_cid,pkpro.cabinet_assembly_price as pkprocap, pkpro.*,pkpro.id as pkpro_id,s.style_name as sn,s.style_code as sc FROM product_order_child p_o_c LEFT JOIN pk_product pkpro on p_o_c.product_id = pkpro.id LEFT JOIN style s on s.id = p_o_c.style WHERE order_id = '$id'";
        $data['product_order_child'] = $this->order_model->custom_query( $sqldata, false, 'array' );

        $sqlChildData = "SELECT p_o_sc.*,p_o_sc.id as p_o_scid,pkpro.cabinet_assembly_price as pkprocap, pkpro.*,pkpro.id as pkpro_id,s.style_name as sn,s.style_code as sc FROM product_order_subchild p_o_sc LEFT JOIN pk_product pkpro on p_o_sc.product_id = pkpro.id LEFT JOIN style s on s.id = p_o_sc.style WHERE order_id = '$id'";
        $data['product_order_sub_child'] = $this->order_model->custom_query( $sqlChildData, false, 'array' );

        $this->load->view('index', $data);
        $this->template->title('Order');
        $this->template
                ->set_layout('inner')
                ->build('product', $data);

    }
    

    function get_product()
    {
        $pData = array();
        $vendor_id = $_POST['vendor_id'];
        $style_id = $_POST['style_id'];
        $cat_id = $_POST['cat_id'];
        $subf_id = $_POST['subone'];
        $subs_id = $_POST['subtwo'];
        
        $where = array(
            '1' => 1
        );
        if ( !empty($vendor_id) ) {
            $where['vendor_id'] = $vendor_id;
        }
        if ( !empty($style_id) ) {
            $where['style_id'] = $style_id;
        }
        if ( !empty($cat_id) ) {
            $where['category'] = $cat_id;
        }
        
        if ( !empty($subf_id) ) {
            $where['sub_category_first'] = $subf_id;
        }
        
        if ( !empty($subs_id) ) {
            $where['sub_category_second'] = $subs_id;
        }
        
        
        $pData = (array) App::get_by_where('pk_product', $where );
        // echo pr($pData);
        // echo $this->db->last_query(); die;
        $json = json_encode($pData);
        echo $json;
    }
    
    function find_product()
    {
        if( isset( $_POST['provalue'] ) ){
            //$sql = "SELECT pkpro.*,pkpro.id as pkid,s.style_code FROM pk_product pkpro JOIN style s on pkpro.style_id = s.id WHERE Item_Name LIKE '".$_POST['provalue']."%'";
            $ids = '';
            foreach ($_POST['provalue'] as $id) {
                $ids .= "'".$id."' ,"; 
            }
            $ids = rtrim($ids,',');

            $sql = "SELECT pkpro.*,pkpro.id as pkid,s.style_code 
                    FROM pk_product pkpro 
                    JOIN style s on pkpro.style_id = s.id 
                    WHERE pkpro.id IN ( $ids ) ";

            $data = $this->order_model->custom_query( $sql, false, 'array' );
            if( !empty( $data ) )
            {
                foreach ( $data as $key => $value ) {
                     $data[] = $value;
                }
                echo json_encode($data);
            }
        }
    }

    function add_product()
    {
        if ( isset( $_POST['productId'] ) ) {
            $productId = $_POST['productId'];

            $sql = "SELECT pkpro.*,pkpro.id as pkpro_id,sy.*,sy.id as sid FROM pk_product pkpro LEFT JOIN style sy on pkpro.style_id = sy.id WHERE pkpro.id = '$productId'";
            $data = $this->order_model->custom_query( $sql, false, 'array' );
            if( !empty( $data ) ){
                echo json_encode($data);
            }else{
                echo 'error';
            }
        }
    }

    function add_product_p()
    {
        if ( isset( $_POST['productId'] ) ) {
            $productId = $_POST['productId'];

            $sql = "SELECT pkpro.*,pkpro.id as pkpro_id,sy.*,sy.id as sid FROM pk_product pkpro LEFT JOIN style sy on pkpro.style_id = sy.id WHERE pkpro.id = '$productId'";
            $data = $this->order_model->custom_query( $sql, false, 'array' );
            if( !empty( $data ) ){
                echo json_encode($data[0]);
            }else{
                echo 'error';
            }
        }
    }
    
    
     function check_product(){
        if ( isset( $_POST ) && !empty( $_POST['skuvalue'] ) ) {

            $edit_order = $this->order_model->findWhere( 'pk_order', array( 'id' => $_POST['id'] ), FALSE, array( '*' ) );

            foreach ($_POST['skuvalue'] as $key => $value) {
                if ( isset($value['undefined']) ) continue;

                $itmCode = $value['Item_Code'];
                $styid = $_POST['Style_ID'];
                $sr = $value['Sr'];
                
                $parentChild = explode('.', $sr);
                

                $this->db->select('pkpro.*,pkpro.id as pkpro_id,pkpro.style_id as pkprost,sy.*,sy.id as sid');
                         $this->db->from('pk_product pkpro');
                         $this->db->join('style sy', 'pkpro.style_id = sy.id', 'left');
                         $this->db->where(array( 'pkpro.Item_Code' => $itmCode, 'pkpro.style_id' => $styid ) );
                $query = $this->db->get();
                $pro_Id = $query->row();

                if ( isset($parentChild[1]) ) {
                    $is_parent = 'no';
                }else{
                    $is_parent = 'yes';
                }

                if ( !empty($pro_Id) ) {
                  if( empty( $pro_Id->Width ) || ( $pro_Id->Width =='NA' ) ){ $width=''; }else{ $width=$pro_Id->Width.'W x '; }
                  if( empty( $pro_Id->Height ) || ( $pro_Id->Height =='NA' ) ){$height='';}else{$height = $pro_Id->Height.'W x ';}
                  if( empty( $pro_Id->Depth ) || ( $pro_Id->Depth =='NA' ) ) {$depth='';}else{$depth = $pro_Id->Depth.'D';}

                  if( $edit_order['assemble_value'] == 1 ){
                    $item_cost    = $pro_Id->item_cost;
                    $cabinet_cost = $pro_Id->unassembled_retail_item_price;
                  } else{
                    $item_cost    = $pro_Id->cabinet_assembly_price;
                    $cabinet_cost = $pro_Id->assembled_retail_item_price;
                  }

                  if( empty($item_cost) || $item_cost == 'NA'  ){
                    $item_cost    = 0;
                  }

                  if( empty($cabinet_cost) || $cabinet_cost == 'NA'  ){
                    $cabinet_cost    = 0;
                  }



                    $proDode = array( 'pkpro_id' => $pro_Id->pkpro_id, 'Quantity' => '1', 'style_Item' => $pro_Id->style_code.'/'.$pro_Id->Item_Code, 'Description' => $pro_Id->style_name.'/'.$pro_Id->Item_description.' '.$width.$height.$depth,'item_cost' => $item_cost,'cabinaet_assemb_c' => $cabinet_cost ,'item_co' => $pro_Id->Item_Code,'sid' => $styid, 'is_parent' => $is_parent,'assembled_retail_item_price' => $pro_Id->assembled_retail_item_price, 'parent_id' => $parentChild[0]);

                    if ($is_parent == 'yes') {
                        $data[$parentChild[0]] = $proDode;
                    }else{
                        $data[$parentChild[0]]['child'][] = $proDode;
                    }
                }else{
                    $styleData =  $this->order_model->findWhere( 'style',array( 'id' => $styid ), False, array('*'));
                    $styleCode = $styleData['style_code'];
                    $styleName = $styleData['style_name'];
                    
                    $proDode = array( 'pkpro_id' => 'temp_'.rand(999,99999), 'Quantity' => '1', 'style_Item' => $styleCode.'/'.$itmCode, 'Description' => $styleName,'item_cost' => 0,'cabinaet_assemb_c' => 0,'item_co' => '','sid' => $styid, 'is_parent' => $is_parent, 'parent_id' => $parentChild[0],'assembled_retail_item_price' => 0, 'not_exist' => 'yes' );

                    if ($is_parent == 'yes') {
                        $data[$parentChild[0]] = $proDode;
                    }else{
                        $data[$parentChild[0]]['child'][] = $proDode;
                    }
                }

            }
            echo json_encode($data);
            exit();
        }
    }

    /*function Import($id)
    {
      $data = array();
      $id = $this->uri->segment(3);
      if(  isset($_POST['submit'] ) ){
          $config = array(
                  'upload_path' => "assets/productOrderfile",
                  'allowed_types' => "xlsx|xls|csv"
                  );
          $this->load->library('upload', $config);
          
          if(!$this->upload->do_upload('file_upload')){
                  $error['error'] = array('error' => $this->upload->display_errors());
          } else {
            $uploaddata = array('upload' => $this->upload->data() );
            $exttrim = ltrim($uploaddata['upload']['file_ext'],".");
                switch ( $exttrim ) {
                  case 'xlsx':
                    $reader = ReaderFactory::create(Type::XLSX);                
                    break;
                  case 'xls':
                    $reader = ReaderFactory::create(Type::XLS);                
                    break;
                  case 'csv':
                    $reader = ReaderFactory::create(Type::CSV);                
                    break;
                }
                $reader->open($uploaddata['upload']['full_path']);
                $count = 1;
                foreach ($reader->getSheetIterator() as $sheet) {             
                    foreach ($sheet->getRowIterator() as $row) {
                        if ($count > 1) {
                            //$query  = $this->order_model->custom('product_order',array('Item_code' => $row[1],'order_id' => ));
                            $mySql = "SELECT * FROM product_order WHERE Item_code = '$row[1]' AND order_id = '$id'";
                            $query = $this->order_model->custom_query( $mySql, TRUE,'array');
                            if( $query == False  ){
                                $pro_Id =  $this->order_model->findWhere( 'pk_product',array( 'Item_Code' => $row[1] ), False, array('id',
                                                'Cabinet_price') );
                                if( !empty( $pro_Id['id'] ) ) { $pro_value = $pro_Id['id']; } else { $pro_value = ''; }
                                if( !empty( $pro_Id['Cabinet_price'] ) ) { $pro_price = $pro_Id['Cabinet_price']; 
                                } else { $pro_price = $row[4] ; }

                                $sql = "INSERT INTO product_order ( order_id, product_id,Item_code, style_id,description,descriptionII, qty, price) VALUES ( '". $id ."', '". $pro_value ."', '". $row[1] ."', '". $row[2] ."', '". $row[3] ."','". $row[4] ."', '". $row[0] ."',
                                         '". $pro_price ."'  )";
                                $query = $this->db->query( $sql );
                            }

                        }
                        $count++;
                    }
                }
                redirect("/orders/product/".$id);
                 $reader->close();
          }

      }
    }*/
    
    function expenses($id)
    {
        $data = array();
        $id = $this->uri->segment(3);

        $data['edit_order'] = $this->order_model->findWhere( 'pk_order', array( 'id' => $id ), FALSE, array( '*' ) );
        $data['expenses_data'] = $this->order_model->findWhere( 'expenses', array( 'order_id' => $id ), TRUE, array( '*' ) ); 
        $this->template->title('Order');
        $this->template
                ->set_layout('inner')
                ->build('expenses', $data);

    }

    function add_expenses($id)
    {   
        $data = array();    
        $id = $this->uri->segment(3);

        $config = array(
            'upload_path'   => 'assets/orderFiles',
            'allowed_types' => '*',
            'overwrite'     => 1,                       
        );
        $this->load->library('upload', $config);

        if( $this->input->post() ){
             if( isset($_FILES['image_upload']) ){
            $files = $_FILES['image_upload'];
            foreach ($files['name'] as $key => $image) {
                $_FILES  = array();
                if ($image != '') {
                    $_FILES['images[]']['name']= $files['name'][$key];
                    $_FILES['images[]']['type']= $files['type'][$key];
                    $_FILES['images[]']['tmp_name']= $files['tmp_name'][$key];
                    $_FILES['images[]']['error']= $files['error'][$key];
                    $_FILES['images[]']['size']= $files['size'][$key];
                    $fileName = $image;
                    $images[] = $fileName;
                    $config['file_name'] = $fileName;
                    $this->upload->initialize($config);
                    if ($this->upload->do_upload('images[]')) {
                        $name[] = $this->upload->data('file_name');
                        
                    } else {
                        $error = array('error' => $this->upload->display_errors());
                        // pr($error);
                    }
                }

              }
            }
            $exp_cat = serialize($_POST['exp_cat']);
            $post_data = array( 
                    'payee'          => $this->input->post('payee'),
                    'payment_date'   => $this->input->post('payee_date'),
                    'payment_method' => $this->input->post('payment_method'),
                    'total'          => $this->input->post('totalExpenses'),
                    'category_meta'  => $exp_cat,
                    'order_id'       => $this->input->post('order_id'),
                    'memo'           => $this->input->post('memo'),
                    'reference_no'   => $this->input->post('reference_no'),
                    'order_files'    => implode(',', $name),

            );
            
            /*pr( $post_data );die;*/

            if( !$this->order_model->insert_data('expenses',$post_data)){
                
                $this->session->set_flashdata('response_status', 'error');
                $this->session->set_flashdata('message', 'Expenses didn\'t save, Try again!');
            
            }else{
                
                $post_data['vendor_id'] = '10';
                $post_data['pay_method'] = '2';
                $url = 'https://hooks.zapier.com/hooks/catch/4221952/ootscge/';
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $resd = curl_exec($ch);
                curl_close($ch);

                //save order logs starts
                log_orders( 
                    $id, 
                    $this->session->userdata('user_id'), 
                    'add_expenses(orders)' 
                );
                $this->session->set_flashdata('response_status', 'success');
                $this->session->set_flashdata('message', 'Expenses has been  add successfully');
            }
            
            redirect('orders/expenses/'.$id);
        }

        $data['exp_cat'] = $this->order_model->fetch_all('expenses_category');
        $this->load->view('modal/add_expenses',$data);
    }

    function edit_expenses($id)
    {   
        $data = array(); 
        $red = $this->input->post('order_id'); 
        $config = array(
                    'upload_path'   => 'assets/orderFiles',
                    'allowed_types' => '*',
                    'overwrite'     => 1,                       
                );
        $this->load->library('upload', $config); 
        if( $this->input->post() ){
            if( isset($_FILES['image_upload']) ){
            $files = $_FILES['image_upload'];
            foreach ($files['name'] as $key => $image) {
                $_FILES  = array();
                if ($image != '') {
                    $_FILES['images[]']['name']= $files['name'][$key];
                    $_FILES['images[]']['type']= $files['type'][$key];
                    $_FILES['images[]']['tmp_name']= $files['tmp_name'][$key];
                    $_FILES['images[]']['error']= $files['error'][$key];
                    $_FILES['images[]']['size']= $files['size'][$key];
                    $fileName = $image;
                    $images[] = $fileName;
                    $config['file_name'] = $fileName;
                    $this->upload->initialize($config);
                    if ($this->upload->do_upload('images[]')) {
                        $name[] = $this->upload->data('file_name');
                                       

                    } else {
                        $error = array('error' => $this->upload->display_errors());
                        pr($error);
                    }
                }

              }
            }

            if( !empty($_POST['eximages']) && !empty( $name )  ){

                $mergeimp   =  array_merge($name,$_POST['eximages']);
                $mergeArray = implode(',', $mergeimp);

            } elseif ( !empty($_POST['eximages']) && empty( $name ) ){

                $mergeArray = implode(',', $_POST['eximages']);

            } elseif ( empty($_POST['eximages']) && !empty( $name ) ) {
                
                $mergeArray = implode(',', $name);
            }

            $exp_cat = serialize($_POST['exp_cat']);
            $post_data = array( 
                    'payee'          => $this->input->post('payee'),
                    'payment_date'   => $this->input->post('payee_date'),
                    'payment_method' => $this->input->post('payment_method'),
                    'total'          => $this->input->post('totalExpenses'),
                    'category_meta'  => $exp_cat,
                    'order_id'       => $this->input->post('order_id'),
                    'memo'           => $this->input->post('memo'),
                    'reference_no'   => $this->input->post('reference_no'),
                    'order_files'    => $mergeArray,
                );

            if( !$this->order_model->updateWhere('expenses',array( 'id' => $this->input->post('expenses_id') ),$post_data)){
                
                $this->session->set_flashdata('response_status', 'error');
                $this->session->set_flashdata('message', 'Expenses didn\'t save, Try again!');
            
            } else {
                
                //save order logs
                log_orders( 
                    $red, 
                    $this->session->userdata('user_id'), 
                    'edit_expenses(order)' 
                );
            
                $this->session->set_flashdata('response_status', 'success');
                $this->session->set_flashdata('message', 'Expenses has been  add successfully');
            }
            
            redirect('orders/expenses/'.$red);
        }

        $data['expenses_data'] = $this->order_model->findWhere( 'expenses', array( 'id' => $id ), FALSE, array( '*' ) ); 
        $data['exp_cat'] = $this->order_model->fetch_all('expenses_category');
        $this->load->view('modal/edit_expenses',$data);
    }
    
    function delete_expenses(){
        if( isset($_POST['ids']) ){
            $ids = $_POST['ids'];
            $order_id = $_POST['order_id'];
            $data['userDel'] = $this->user_model->Pkdelete_query($ids,'expenses');
            if($data == TRUE){
                
                //save order logs
                log_orders( 
                    $order_id, 
                    $this->session->userdata('user_id'), 
                    'delete_expenses(orders)' 
                );
                
                echo "TRUE";
            } else {
                echo "FALSE";
            }
        }
    }

    function view_po($id)
    {
        $id   = $this->uri->segment(3);
        $data = array();

        $data['pro_view'] = $this->db->select('pk.*,p_o.*,pk.id as pkid,pk.Item_Code as pkIN,pk.Item_description as pkIdes,pk.cabinet_assembly_price as pkcap,st.*,p_o.qty')
                ->from('product_order p_o')->join('pk_product pk', 'pk.id = p_o.product_id')->join('style st','st.id = pk.style_id')->where( 'order_id',$id )->get()->result();
        //echo $this->db->last_query();
        
        
       $data['order_view'] = $this->order_model->findWhere( 'pk_order', array( 'id' => $id ), FALSE, array( '*' ) );
        //$data['pro_view'] = $this->order_model->findWhere( 'product_order', array( 'order_id' => $id ), TRUE, array( '*' ) );
        
    
        $data['pro_view_child'] = $this->order_model->findWhere( 'product_order_child', array( 'order_id' => $id ), TRUE, array( '*' ) );
        $this->load->view('index', $data);
        $this->template->title('Order View');
        $this->template
                ->set_layout('inner')
                ->build('view_po', $data);        

    }

    function quote($id)
    {
        $id   = $this->uri->segment(3);
        $data = array();

        $data['pro_view'] = $this->db->select('pk.*,p_o.*,pk.id as pkid,pk.Item_Code as pkIN,pk.Item_description as pkIdes,pk.cabinet_assembly_price as pkcap,st.*,p_o.qty')
                ->from('product_order p_o')->join('pk_product pk', 'pk.id = p_o.product_id')->join('style st','st.id = pk.style_id')->where( 'order_id',$id )->get()->result();
        //echo $this->db->last_query();
        
        
        $data['order_view'] = $this->order_model->findWhere( 'pk_order', array( 'id' => $id ), FALSE, array( '*' ) );
        $data['pro_view_child'] = $this->order_model->findWhere( 'product_order_child', array( 'order_id' => $id ), TRUE, array( '*' ) );

        $sqldata = "SELECT p_o_c.*,p_o_c.id as p_o_cid,pkpro.cabinet_assembly_price as pkprocap, pkpro.*,pkpro.id as pkpro_id,s.style_name as sn,s.style_code as sc FROM product_order_child p_o_c LEFT JOIN pk_product pkpro on p_o_c.product_id = pkpro.id LEFT JOIN style s on s.id = p_o_c.style WHERE order_id = '$id'";
        $data['product_order_child'] = $this->order_model->custom_query( $sqldata, false, 'array' );

        $sqlChildData = "SELECT p_o_sc.*,p_o_sc.id as p_o_scid,pkpro.cabinet_assembly_price as pkprocap, pkpro.*,pkpro.id as pkpro_id,s.style_name as sn,s.style_code as sc FROM product_order_subchild p_o_sc LEFT JOIN pk_product pkpro on p_o_sc.product_id = pkpro.id LEFT JOIN style s on s.id = p_o_sc.style WHERE order_id = '$id'";
        $data['product_order_sub_child'] = $this->order_model->custom_query( $sqlChildData, false, 'array' );

        $this->load->view('index', $data);
        $this->template->title('Order View');
        $this->template
                ->set_layout('inner')
                ->build('quote', $data);        

    }

    function contract($id)
    {   
        $id   = $this->uri->segment(3);
        $data = array();

        $data['pro_view'] = $this->db->select('pk.*,p_o.*,pk.id as pkid,pk.Item_Code as pkIN,pk.Item_description as pkIdes,pk.cabinet_assembly_price as pkcap,st.*,p_o.qty')
                ->from('product_order p_o')->join('pk_product pk', 'pk.id = p_o.product_id')->join('style st','st.id = pk.style_id')->where( 'order_id',$id )->get()->result();
        //echo $this->db->last_query();
        
        
       $data['order_view'] = $this->order_model->findWhere( 'pk_order', array( 'id' => $id ), FALSE, array( '*' ) );
        //$data['pro_view'] = $this->order_model->findWhere( 'product_order', array( 'order_id' => $id ), TRUE, array( '*' ) );

        $sqldata = "SELECT p_o_c.*,p_o_c.id as p_o_cid,pkpro.cabinet_assembly_price as pkprocap, pkpro.*,pkpro.id as pkpro_id,s.style_name as sn,s.style_code as sc FROM product_order_child p_o_c LEFT JOIN pk_product pkpro on p_o_c.product_id = pkpro.id LEFT JOIN style s on s.id = p_o_c.style WHERE order_id = '$id'";
        $data['product_order_child'] = $this->order_model->custom_query( $sqldata, false, 'array' );

        $sqlChildData = "SELECT p_o_sc.*,p_o_sc.id as p_o_scid,pkpro.cabinet_assembly_price as pkprocap, pkpro.*,pkpro.id as pkpro_id,s.style_name as sn,s.style_code as sc FROM product_order_subchild p_o_sc LEFT JOIN pk_product pkpro on p_o_sc.product_id = pkpro.id LEFT JOIN style s on s.id = p_o_sc.style WHERE order_id = '$id'";
        $data['product_order_sub_child'] = $this->order_model->custom_query( $sqlChildData, false, 'array' );
        
        $data['payment_data'] = $this->order_model->findWhere( 'customer_payment', array( 'order_id' => $id ), TRUE, array( 'payment_amount' ) );
        $data['pro_view_child'] = $this->order_model->findWhere( 'product_order_child', array( 'order_id' => $id ), TRUE, array( '*' ) );
        $this->load->view('index', $data);
        $this->template->title('Order View');
        $this->template
                ->set_layout('inner')
                ->build('contract', $data);        

    }

    function purchase_order($id)
    {
        $id   = $this->uri->segment(3);
        $data = array();

        $data['pro_view'] = $this->db->select('pk.*,p_o.*,pk.id as pkid,pk.Item_Code as pkIN,pk.Item_description as pkIdes,pk.cabinet_assembly_price as pkcap,st.*,p_o.qty')
                ->from('product_order p_o')->join('pk_product pk', 'pk.id = p_o.product_id')->join('style st','st.id = pk.style_id')->
                where( 'order_id',$id )->get()->result();
        //echo $this->db->last_query();
        
        
        $data['order_view'] = $this->order_model->findWhere( 'pk_order', array( 'id' => $id ), FALSE, array( '*' ) );
        $vid = $data['order_view']['vendor'];
        $data['my_vendor']  = $this->order_model->findWhere( 'vendor', array( 'id' => $vid ), FALSE, array( '*' ) );
        $sqldata = "SELECT p_o_c.*,p_o_c.id as p_o_cid,pkpro.cabinet_assembly_price as pkprocap, pkpro.*,pkpro.id as pkpro_id,s.style_name as sn,s.style_code as sc FROM product_order_child p_o_c LEFT JOIN pk_product pkpro on p_o_c.product_id = pkpro.id LEFT JOIN style s on s.id = p_o_c.style WHERE order_id = '$id'";
        $data['product_order_child'] = $this->order_model->custom_query( $sqldata, false, 'array' );

        $sqlChildData = "SELECT p_o_sc.*,p_o_sc.id as p_o_scid,pkpro.cabinet_assembly_price as pkprocap, pkpro.*,pkpro.id as pkpro_id,s.style_name as sn,s.style_code as sc FROM product_order_subchild p_o_sc LEFT JOIN pk_product pkpro on p_o_sc.product_id = pkpro.id LEFT JOIN style s on s.id = p_o_sc.style WHERE order_id = '$id'";
        $data['product_order_sub_child'] = $this->order_model->custom_query( $sqlChildData, false, 'array' );

        $this->load->view('index', $data);
        $this->template->title('Order View');
        $this->template
                ->set_layout('inner')
                ->build('purchase_order', $data);        

    }

    function print_po($id)
    {
        $id   = $this->uri->segment(3);
        $data = array();
        $data['order_view'] = $this->order_model->findWhere( 'pk_order', array( 'id' => $id ), FALSE, array( '*' ) );
        $data['pro_view'] = $this->db->select('pk.*,p_o.*,pk.id as pkid,pk.Item_Code as pkIN,pk.Item_description as pkIdes,pk.cabinet_assembly_price as pkcap,st.*,p_o.qty')
                ->from('product_order p_o')->join('pk_product pk', 'pk.id = p_o.product_id')->join('style st','st.id = pk.style_id')->where( 'order_id',$id )->get()->result();
        //echo $this->db->last_query();
        $data['pro_view_child'] = $this->order_model->findWhere( 'product_order_child', array( 'order_id' => $id ), TRUE, array( '*' ) );
        $this->load->view('index', $data);
        $this->template->title('Order View');
        $this->template
             ->set_layout('inner')
             ->build('print_po', $data);        

    }

    function files($id)
    {
       $id = $this->uri->segment(3);
        $config = array(
            'upload_path'   => 'assets/leadsfiles',
            'allowed_types' => '*',
            'overwrite'     => 1,                       
        );

        $this->load->library('upload', $config);
    
        if( isset($_FILES['files'] )  ){
            
            $files = $_FILES['files'];
            foreach ($files['name'] as $key => $image) {
                $_FILES  = array();
                if ($image != '') {
                    $_FILES['images[]']['name']= $files['name'][$key];
                    $_FILES['images[]']['type']= $files['type'][$key];
                    $_FILES['images[]']['tmp_name']= $files['tmp_name'][$key];
                    $_FILES['images[]']['error']= $files['error'][$key];
                    $_FILES['images[]']['size']= $files['size'][$key];
                    $tmp = substr(time(), 5) ;
                    $fileName = $key .'__'.$tmp.'_'.$image;
                    $images[] = $fileName;
                    $config['file_name'] = $fileName;
                    $this->upload->initialize($config);
                   if ($this->upload->do_upload('images[]')) {
                        $name[] = $this->upload->data('file_name');
                        
                    } else {
                        $error = array('error' => $this->upload->display_errors());
                        pr($error);
                    }
                }

            }
            
            if( isset( $_POST['upload'] ) ){
                $result = [];
                foreach ($_POST['upload'] as $key => $value) {
                    if( !empty( $value ) )
                        $result = array_merge($result, $value);
                }
            }
            if( !empty( $result ) ){

                if( !empty($name) ){
                    $mergeName = array_merge($name,$result);
                    $NewArray = implode( ',', $mergeName);    
                
                }else{
                    $NewArray = implode( ',', $result);    
                }
            
            } else {
                $NewArray = implode( ',', $name); 
            }


            $data = array( 
                    'field_id'     => $id,
                    'module_name'  => 'order-'.$id,
                    'file_name'    => $NewArray,
                    'title'        => '',
                    'path'         => $this->upload->data('file_path'),
                    'ext'          => $this->upload->data('file_ext'),
                    'size'         => $this->upload->data('file_size'),
                    'is_image'     => $this->upload->data('is_image'),
                    'created_by'   => $this->tank_auth->get_user_id()
            );
          
            
            $this->db->select('*');
            $this->db->where(array( 'field_id' => $id, 'module_name' => 'order-'.$id  ));
            $query = $this->db->get('files');
            $num = $query->num_rows();
            
            if( $num > 0  ){
                
                $this->order_model->deleteWhere( 'files', array( 'field_id' => $id, 'module_name' => 'order-'.$id  ) );
                if( !$this->order_model->insert_data('files',$data)){
                
                    $this->session->set_flashdata('response_status', 'error');
                    $this->session->set_flashdata('message', 'Order didn\'t save, Try again!');
            
                } else {

                    //save order logs
                    log_orders( 
                        $id, 
                        $this->session->userdata('user_id'), 
                        'files(orders)' 
                    );
            
                    $this->session->set_flashdata('response_status', 'success');
                    $this->session->set_flashdata('message', 'File saved successfully');
                }
                
            } else {
                
                if( !$this->order_model->insert_data('files',$data)){
                
                    $this->session->set_flashdata('response_status', 'error');
                    $this->session->set_flashdata('message', 'File didn\'t save, Try again!');
            
                } else {

                    //save order logs
                    log_orders( 
                        $id, 
                        $this->session->userdata('user_id'), 
                        'files(orders)' 
                    );

                    $this->session->set_flashdata('response_status', 'success');
                    $this->session->set_flashdata('message', 'File saved successfully');
                }
                
            }
            
            redirect('orders/files/'.$id);
        }

        $data['edit_order'] = $this->order_model->findWhere( 'pk_order', array( 'id' => $id ), FALSE, array( '*' ) );
        $data['file_data'] = $this->order_model->findWhere('files', array('field_id' => $id,'module_name' => 'order-'.$id ), array('*') );
        $this->template->title('File');
        $this->template
                  ->set_layout('inner')
                  ->build('files', $data);
  }
  

    function Dashboard($id)
    {
        $data = array();
        $id = $this->uri->segment(3);
        
        if( $this->input->post() ){
            $post_data =array(
                    'status'                     => $this->input->post('status'),                
                    'vendor_invoice'             => $this->input->post('vendor_invoice'),  
                    'refunded'                   => $this->input->post('refunded'),               
                    'first_name'                 => $this->input->post('first_name'),               
                    'last_name'                  => $this->input->post('last_name'),                
                    'estimated_delivery_date'    => $this->input->post('estimated_delivery_date'),  
                    'estimated_date_notes'       => $this->input->post('estimated_date_notes'),          
                    'survey_date'                => $this->input->post('survey_date'),
                    'survey'                     => $this->input->post('survey'),
                    'schedule_delivery_date'     => $this->input->post('schedule_delivery_date'),
                    'sales_primary'              => $this->input->post('sales_primary'),
                    'sales_secondary'            => $this->input->post('sales_secondary')
                );
            
          if( !$order_id = App::update( 'pk_order' ,array('id' => $id), $post_data ) )
            {
                $this->session->set_flashdata('response_status', 'error');
                $this->session->set_flashdata('message', 'Order didn\'t save, Try again!');
            }
            else
            {
                //saving order logs
                log_orders( 
                    $id,
                    $this->session->userdata('user_id'),
                    'Dashboard' 
                );
            
                $this->session->set_flashdata('response_status', 'success');
                $this->session->set_flashdata('message', 'Order has been  add successfully');

            }
            redirect('/orders/dashboard/'.$id);
            
        }
        
        $data['my_vendorusers'] = $this->db->select('pk_order.vendor,vendor.*')->from('pk_order')->join( 'vendor','pk_order.vendor = vendor.id' )
                ->where( 'pk_order.id',$id )->get()->result();
        
        $data['users'] = $this->order_model->fetch_all('user_profiles');
        
        $data['edit_order'] = $this->order_model->findWhere( 'pk_order', array( 'id' => $id ), FALSE, array( '*' ) );
        
        $data['payment'] = $this->order_model->findWhere( 'customer_payment', array( 'order_id' => $id ), TRUE, array( 'payment_amount' ) );

        $data['expenses_amt'] = $this->order_model->findWhere( 'expenses', array( 'order_id' => $id ), TRUE, array( 'total' ) );

        $data['pro_view'] = $this->order_model->findWhere( 'product_order', array( 'order_id' => $id ), TRUE, array( '*' ) );
        $this->template->title('Order View');
        $this->template
                ->set_layout('inner')
                ->build('dashboard', $data);

    }
      
    function load_script()
    {
        $data = array();
        $this->load->view('load_script', $data);
    }

    function load_css()
    {
        $data = array();
        $this->load->view('load_css', $data);
    }

    function check_email(){

        if( $email = $_POST['email'])
        {
            $count = $this->crm_model->check_data_exist($table = 'leads', $key = 'email', $val = $email);
            if( $count > 0 ){
                echo "true";
            }
        }
    }
    
    public function payment($id){
        $data = array();
        $id = $this->uri->segment(3);
        $data['payment_data'] = $this->order_model->findWhere( 'customer_payment', array( 'order_id' => $id ), TRUE, array( '*' ) );
        $data['total_amount'] = $this->order_model->findWhere( 'pk_order', array( 'id' => $id ), FALSE, array( '*' ) );

        $this->template->title('Payment');
        $this->template
                ->set_layout('inner')
                ->build('payment', $data);
    }

    public function add_payment($id){

        $data = array();

        $id = $this->input->post('order_id');
        
        if( $this->input->post() ){
            $post_data = array(             
                'order_id'           => $id,
                'payment_method '    => $this->input->post('payment_method'),                     
                'payment_amount'     => $this->input->post('payment_amount'),             
                'reference_no'       => $this->input->post('reference_no'),
                'created_date'       => date('Y-m-d h:i:s'),          
                'add_by_user'        => $this->session->userdata('user_id')        
            );
            $orderMeta = $this->order_model->findWhere( 'pk_order', array( 'id' => $id ), FALSE, array( '*' ) );
            if( $orderMeta['status'] != 'Order'){
                App::update( 'pk_order' ,array('id' => $id), array( 'status' => 'Pre-Order' ) );
            }

            if( !$this->order_model->insert_data('customer_payment',$post_data ) ){
                $this->session->set_flashdata('response_status', 'error');
                $this->session->set_flashdata('message', 'Payment didn\'t save, Try again!');
            }else{
                //saving order logs
                log_orders( 
                    $id, 
                    $this->session->userdata('user_id'), 
                    'add_payment(orders)' 
                );
                $this->session->set_flashdata('response_status', 'success');
                $this->session->set_flashdata('message', 'Payment has been added successfully');    
            }
            redirect('/orders/payment/'.$id);
        }
        $this->load->view('modal/add_payment');
    }

    public function edit_payment($id){


        $data = array();
        
        if( $this->input->post() ){
            $post_data = array(             
                    'payment_method '    => $this->input->post('payment_method'),                     
                    'payment_amount'     => $this->input->post('payment_amount'),             
                    'reference_no'       => $this->input->post('reference_no')
                );
                $o_id = $this->input->post('order_id');
                $p_id = $this->input->post('id');

             if( !$this->order_model->updateWhere('customer_payment',array( 'id' => $p_id ),$post_data)){
                
                $this->session->set_flashdata('response_status', 'error');
                $this->session->set_flashdata('message', 'Payment didn\'t save, Try again!');
            
            }else{
                
                //saving order logs
                log_orders( 
                    $o_id, 
                    $this->session->userdata('user_id'), 
                    'edit_payment(orders)' 
                );

                $this->session->set_flashdata('response_status', 'success');
                $this->session->set_flashdata('message', 'Payment has been updated successfully');    
            }
            redirect('/orders/payment/'.$o_id);
        }

       $data['payment_data'] = $this->order_model->findWhere( 'customer_payment', array( 'id' => $id), FALSE, array( '*' ) );
        $this->load->view('modal/edit_payment',$data);
    }

    function delete_payment(){
        if ( isset($_POST['ids']) ) {
            
            $delete = $this->order_model->deleteWhere( 'customer_payment',array( 'id' => $_POST['ids'] ) );
                
            if ( $delete ) {
                //saving order logs                
                echo 'TRUE';
            }else{
                echo 'False';
            }
        }
    }
    
    
    function order_row(){
        $column = array(
                      0 => 'id',
                      1 => 'status',
                      2 => 'fname',
                      3 => 'phone',
                      4 => 'sales_primary',
                      5 => 'vdrcode',
                      6 => 'schedule_delivery_date',
                      7 => 'total',
                      8 => 'paid',
                      9 => 'total_due'
                    );
        $requestData= $_REQUEST;
        $vendor_id = $this->session->userdata('vendor_id');
        $sql = "SELECT pkr.*,pkr.id as pkid, CONCAT(pkr.first_name, ' ', pkr.last_name) AS fname, cust.full_name, cust.phone, vdr.code as vdrcode,up.name as upname ,
            ( SELECT SUM( payment_amount ) FROM customer_payment WHERE order_id = pkr.id ) AS payment_amount
             FROM pk_order pkr 
                LEFT JOIN  customer cust on pkr.customer_id = cust.id LEFT JOIN vendor vdr on vdr.id = pkr.vendor LEFT JOIN user_profiles up on pkr.sales_primary = up.user_id WHERE ( status IS NULL OR status <> 'Quote' ) "; 
        $totalData = $this->order_model->custom_query($sql,true,'array');
        $totalFiltered = $totalData;
        
        if( !empty($requestData['search']['value']) ) { 
            $sql.= " AND ( pkr.id LIKE '".$requestData['search']['value']."%'";    
            $sql.= " OR pkr.status LIKE '%".$requestData['search']['value']."%' ";    
            $sql.= " OR pkr.first_name LIKE '%".$requestData['search']['value']."%' ";
            $sql.= " OR pkr.last_name LIKE '%".$requestData['search']['value']."%' ";
            $sql.= " OR cust.phone LIKE '%".$requestData['search']['value']."%' ";
            $sql.= " OR vdr.name LIKE '%".$requestData['search']['value']."%' ";
            $sql.= " OR up.name LIKE '%".$requestData['search']['value']."%' ";
            $sql.= " OR pkr.sales_secondary LIKE '%".$requestData['search']['value']."%' ";
            $sql.= " OR pkr.vendor_invoice LIKE '%".$requestData['search']['value']."%' ";
            $sql.= " OR pkr.total LIKE '%".$requestData['search']['value']."%' ";
            $sql.= " OR pkr.paid LIKE '%".$requestData['search']['value']."%' ";
            $sql.= " OR pkr.total_due LIKE '%".$requestData['search']['value']."%'";
            $sql.= " OR pkr.is_pickup LIKE '%".$requestData['search']['value']."%' )";
            
        }

        $totalFiltered = $this->order_model->custom_query($sql,true,'array');
        $sql.=" ORDER BY ". $column[$requestData['order'][0]['column']]."   ".$requestData['order'][0]['dir']."  LIMIT ".$requestData['start']." ,".$requestData['length']."   ";
        $total_record = $this->order_model->custom_query($sql,false,'array');
        $data = array();
        if( is_array( $total_record ) && !empty( $total_record ) ) {
            
            foreach ($total_record as $row) {
                        
                //pr( $row );

                ( $row['subtotal'] ) ? $total_sub = floatval($row['subtotal']) + floatval($row['delivery_price']) - floatval($row['discount']): $total_sub = '';
                

                ( $total_sub ) ? $pro_per = (floatval($total_sub) - floatval($row['cost_t_price']))*100/floatval($total_sub): $pro_per = '0.00%'; 


                /*if ( $row['status'] != 'Quote' ) {*/
                
                        if( !empty($row['payment_amount']) ){ $paid = $row['payment_amount']; }else{ $paid = 0; }
                        if( !empty($row['total']) ){ $totalp = $row['total']; }else{ $totalp = 0; }
                        ( $row['schedule_delivery_date'] ) ? $sd = $row['schedule_delivery_date'].'<br>': $sd = '';
                        $total_due = $totalp - $paid;
                        if( !empty($paid) && !empty($totalp) ){
                            
                            $paidN = ($total_due * 100)/$totalp;
                            $paidP = number_format((float)$paidN, 2, '.', '').'%';
                        } else {
                            $paidP = number_format((float)0, 2, '.', '').'%';
                        }
                        

                        if( $row['vendor_invoice']  ){ $vIn = '#'.$row['vendor_invoice']; }else{ $vIn =''; };
                        
                        $id = '<a href="'.base_url().'orders/Dashboard/'.$row['pkid'].'">'.$row['pkid'].'<br>'.$vIn.'</a>';

                        $data[] = array( 
                           $id, 
                            empty($row['status'])? 'N/A' : $row['status'],
                            $row['fname'],
                            empty( $row['phone'] )?'N/A' : $row['phone'],
                            empty( $row['upname'] )?'N/A' : $row['upname'],
                            empty( $row['vdrcode'] )?'N/A' : ucfirst($row['vdrcode']),
                            $sd.$row['requested_delivery_date'],
                            '$'.number_format($row['total'],2),
                            '$'.number_format($row['payment_amount'],2),
                            '$'.number_format($total_due,2).'<br>'.$paidP,
                            number_format($pro_per,2).'% P',
                            empty( $row['is_pickup'] )? 'N/A' : ucfirst($row['is_pickup']), 
                            '<a href="'.base_url().'orders/Dashboard/'.$row['pkid'].'" title="Dashboard" class="action-icon" data-animation="" data-plugin="" data-overlaycolor="#38414a"> <i class="fas fa-eye"></i></a>
                            <a href="javascript:void(0);" id="deleteOrder" title="Delete" class="action-icon" ids="'. $row['pkid'].'"> <i class="mdi mdi-delete"></i></a>'
                            );
                        /*}*/
                }
            }
        
            $output = array(
                "draw"            => $_POST['draw'],
                "recordsTotal"    => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data"            => $data,
            );
        
        echo json_encode($output);

     }

     function quote_orders(){
        $this->template->title('Quote Order');
        $this->template
                ->set_layout('inner')
                ->build('quote_orders', $data);
     }

     function orders_quote(){
        $column = array(
                      0 => 'id',
                      1 => 'status',
                      2 => 'fname',
                      3 => 'phone',
                      4 => 'sales_primary',
                      5 => 'vdrcode',
                      6 => 'schedule_delivery_date',
                      7 => 'total',
                      8 => 'paid',
                      9 => 'total_due'
                    );
        $data = array();
        $requestData = $_REQUEST;
        $vendor_id   = $this->session->userdata('vendor_id');
        $vendor_id   = 1;
        $sql = "SELECT pkr.*,pkr.id as pkid, CONCAT(pkr.first_name, ' ', pkr.last_name) AS fname, cust.full_name, cust.phone, vdr.code as vdrcode,up.name as upname,
            ( SELECT SUM( payment_amount ) FROM customer_payment WHERE order_id = pkr.id ) AS payment_amount FROM pk_order pkr 
                LEFT JOIN  customer cust on pkr.customer_id = cust.id LEFT JOIN vendor vdr on vdr.id = pkr.vendor LEFT JOIN user_profiles up on pkr.sales_primary = up.user_id WHERE status = 'Quote' "; 
        $totalData = $this->order_model->custom_query($sql,true,'array');
        $totalFiltered = $totalData;
        
        if( !empty($requestData['search']['value']) ) { 
            $sql.= " AND ( pkr.id LIKE '".$requestData['search']['value']."%'";    
            $sql.= " OR pkr.status LIKE '%".$requestData['search']['value']."%' ";    
            $sql.= " OR pkr.first_name LIKE '%".$requestData['search']['value']."%' ";
            $sql.= " OR pkr.last_name LIKE '%".$requestData['search']['value']."%' ";
            $sql.= " OR cust.phone LIKE '%".$requestData['search']['value']."%' ";
            $sql.= " OR vdr.name LIKE '%".$requestData['search']['value']."%' ";
            $sql.= " OR up.name LIKE '%".$requestData['search']['value']."%' ";
            $sql.= " OR pkr.sales_secondary LIKE '%".$requestData['search']['value']."%' ";
            $sql.= " OR pkr.vendor_invoice LIKE '%".$requestData['search']['value']."%' ";
            $sql.= " OR pkr.total LIKE '%".$requestData['search']['value']."%' ";
            $sql.= " OR pkr.paid LIKE '%".$requestData['search']['value']."%' ";
            $sql.= " OR pkr.total_due LIKE '%".$requestData['search']['value']."%'";
            $sql.= " OR pkr.is_pickup LIKE '%".$requestData['search']['value']."%' )";
            
        }
        
        $totalFiltered = $this->order_model->custom_query($sql,true,'array');
        $sql.=" ORDER BY ". $column[$requestData['order'][0]['column']]."   ".$requestData['order'][0]['dir']."  LIMIT ".$requestData['start']." ,".$requestData['length']."   ";
        $total_record = $this->order_model->custom_query($sql,false,'array');
        if( is_array( $total_record ) && !empty( $total_record ) ) {
            
            foreach ($total_record as $row) {
                /*pr( $row,1 );*/
                ( $row['subtotal'] ) ? $total_sub = floatval($row['subtotal']) + floatval($row['delivery_price']) - floatval($row['discount']): $total_sub = '';
                ( $total_sub ) ? $pro_per = (floatval($total_sub) - floatval($row['cost_t_price']))*100/floatval($total_sub): $pro_per = '0.00%'; 
                if ( $row['status'] == 'Quote' ) {
                        if( !empty($row['payment_amount']) ){ $paid = $row['payment_amount']; }else{ $paid = 0; }
                        if( !empty($row['total']) ){ $totalp = $row['total']; }else{ $totalp = 0; }
                        ( $row['schedule_delivery_date'] ) ? $sd = $row['schedule_delivery_date'].'<br>': $sd = '';
                        $total_due = $totalp - $paid;
                        if( !empty($paid) && !empty($totalp) ){
                            
                            $paidN = ($total_due * 100)/$totalp;
                            $paidP = number_format((float)$paidN, 2, '.', '').'%';
                        } else {
                            $paidP = number_format((float)0, 2, '.', '').'%';
                        }
                        if( $row['vendor_invoice']  ){ $vIn = '#'.$row['vendor_invoice']; }else{ $vIn =''; };
                        $id = '<a href="'.base_url().'orders/Dashboard/'.$row['pkid'].'">'.$row['pkid'].'<br>'.$vIn.'</a>';
                        
                        $data[] = array( 
                           $id, 
                            empty($row['status'])? 'N/A' : $row['status'],
                            $row['fname'],
                            empty( $row['phone'] )?'N/A' : $row['phone'],
                            empty( $row['upname'] )?'N/A' : $row['upname'],
                            empty( $row['vdrcode'] )?'N/A' : ucfirst($row['vdrcode']),
                            $sd.$row['requested_delivery_date'],
                            '$'.number_format($row['total'],2),
                            '$'.number_format($row['payment_amount'],2),
                            '$'.number_format($total_due,2).'<br>'.$paidP,
                            empty( $row['is_pickup'] )? 'N/A' : ucfirst($row['is_pickup']), 
                            '<a href="'.base_url().'orders/Dashboard/'.$row['pkid'].'" title="Dashboard" class="action-icon" data-animation="" data-plugin="" data-overlaycolor="#38414a"> <i class="fas fa-eye"></i></a>
                            <a href="javascript:void(0);" id="deleteOrder" title="Delete" class="action-icon" ids="'. $row['pkid'].'"> <i class="mdi mdi-delete"></i></a>'
                            );

                        }
                }
            }
        
            $output = array(
                "draw"            => $_POST['draw'],
                "recordsTotal"    => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data"            => $data,
            );
        echo json_encode($output);
     }


   /**
    *Retrieve order emails 
    *View emails.php
    */
    // public function emails()
    // {
    //     $data = array();
    //     $this->template->title('Order Emails');
    //     $this->template
    //             ->set_layout('inner')
    //             ->build('emails', $data);        
    // }
    // emails() ends

    /**
    *order dashboard email tab
    *View emails.php dashboard tab
    *Call From Script load_script.php
    */
    public function get_emails()
    {
        $columns = array(
            0 => 'id',
            1 => 'subject',
            2 => 'message',
            3 => 'customer_name'
        );
       
        $sql = "SELECT * FROM order_emails t WHERE 1=1 ";

        $totalData = $this->order_model->custom_query($sql,true,'array');
        $totalFiltered = $totalData;

        if( !empty( $this->input->post('search')['value'] ) ){
            $sql.= "AND ( t.id LIKE '%" . $_REQUEST['search']['value'] . "%'";
            $sql.= "OR t.subject LIKE '%".$_REQUEST['search']['value']. "%'";
            $sql.= "OR t.message LIKE'%" .$_REQUEST['search']['value']."%'";
            $sql.= "OR t.customer_name LIKE'%" .$_REQUEST['search']['value']."%')";
        }

        $totalFiltered = $this->order_model->custom_query($sql,true,'array'); 

        $sql.=" ORDER BY ". $columns[$_REQUEST['order'][0]['column']]."   ".$_REQUEST['order'][0]['dir']."  LIMIT ".$_REQUEST['start']." ,".$_REQUEST['length']."   ";

        $total_record = $this->order_model->custom_query($sql,false,'array'); 
               
        $data = array();        
        
        if (!empty($total_record)) {
            foreach ($total_record as $email) {
                                        
                $data[] = array( 
                        $email['id'],  
                        $email['subject'], 
                        substr($email['message'], 0, 15).'...',
                        $email['customer_name'],
                        '<a href="'.base_url().'orders/view_email/'. $email['id'].'" title="Edit task" class="action-icon" data-animation="fadein" data-plugin="custommodalEdit" data-overlaycolor="#38414a"> <i class="fas fa-eye"></i></a>'
                       //'<a href="javascript:void(0);" id="deleteEmail" title="Delete" class="action-icon" ids="'. $email['id'].'"> <i class="mdi mdi-delete"></i></a>'
                );
            }
        }

        $output = array(
            "draw" => $_POST['draw'],
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data
        );
        
        echo json_encode($output);
    }
    // get_emails() ends

    /**
    *order dashboard email tab action edit popup
    *View modal/email_edit.php
    */
    public function view_email($id)
    {
        $data = array(); 
        $data['email'] = (array) App::get_row_by_where('order_emails', array( 'id' => $id ) );
        $this->load->view('modal/view_email', $data);
    }
    // view_email() ends
    
    /**
    * get email session 
    *View/emails
    */
    public function emails(){
        $id = $this->uri->segment(3);
        $result = $this->db->select('*')->from('order_emails')->get();
        $data['order_emails'] = $result->result('array');
        $data['edit_order'] = $this->order_model->findWhere( 'pk_order', array( 'id' => $id ), FALSE, array( '*' ) );
        $this->template->title('survey');
        $this->template
                    ->set_layout('inner')
                    ->build('emails', $data);
    }// emails() ends
    
    /*
    * View Order Logs
    */
    public function logs($id){
        $data = array();
        $id = $this->uri->segment(3);
        $data['order_det'] = (array) App::get_row_by_where('pk_order', array( 'id' => $id ) );
        $data['edit_order'] = $this->order_model->findWhere( 'pk_order', array( 'id' => $id ), FALSE, array( '*' ) );
        $data['logs'] =  (array) App::get_by_where('order_logs', array( 'order_id' => $id ), array( 'column' => 'id', 'order' => 'DESC' ), true );
        $this->template->title('Logs');
        $this->template
            ->set_layout('inner')
            ->build('logs', $data);   
    }

    /*
    * Restore Order backup
    * called from js view/logs
    */
    public function restore_order() {
        
        if( isset($_POST['ids']) ){
        
            $id = $_POST['ids'];
            $log_data = (array) App::get_row_by_where('order_logs', array( 'id' => $id ) );
            $order_id = $log_data['order_id'];
            $vendor_id = $log_data['vendor_id'];
            
            // restores pk_order data
            $order_data = json_decode( $log_data['order_data'] , true );
            $order_data = array_merge( $order_data , array( 'current_version' => $id ) );
            unset($order_data['id']);
            $update_id = App::update( 'pk_order',array( 'id' => $order_id ), $order_data );
            
            if ($vendor_id == 1) {
               $tblOrder            = 'product_order';
               $tblOrderchild       = 'product_order_child';
               $tblOrderSubChild    = 'product_order_subchild';
            } else if ($vendor_id == 2) {
                $tblOrder           = 'fab_product_order';
                $tblOrderchild      = '';
                $tblOrderSubChild   = '';
            } else if ($vendor_id == 3) {
                $tblOrder           = 'jk_product_order';
                $tblOrderchild      = 'jk_product_order_child';
                $tblOrderSubChild   = 'jk_product_order_subchild';
            } else if ($vendor_id == 4) {
                $tblOrder           = 'cnc_product_order';
                $tblOrderchild      = 'cnc_product_order_child';
                $tblOrderSubChild   = 'cnc_product_order_subchild';
            } else if ($vendor_id == 5) {
                $tblOrder           = 'wc_product_order';
                $tblOrderchild      = 'wc_product_order_child';
                $tblOrderSubChild   = 'wc_product_order_subchild';
            } else if ($vendor_id == 6) {
                $tblOrder           = 'tknobs_product_order';
                $tblOrderchild      = 'tknobs_product_order_child';
                $tblOrderSubChild   = 'tknobs_product_order_subchild';
            } else if ($vendor_id == 7) {
                $tblOrder           = 'msiTile_product_order';
                $tblOrderchild      = 'msiTile_product_order_child';
                $tblOrderSubChild   = 'msiTile_product_order_subchild';
            } else if ($vendor_id == 8) {
                $tblOrder           = 'asfg_product_order';
                $tblOrderchild      = '';
                $tblOrderSubChild   = '';
            } else if ($vendor_id == 9) {
                $tblOrder           = '21century_product_order';
                $tblOrderchild      = '21century_product_order_child';
                $tblOrderSubChild   = '21century_product_order_subchild';
            }
            
            // restores product_order data
            $product_data = json_decode( $log_data['product_data'] , true );
            $this->order_model->deleteWhere( $tblOrder,array( 'order_id' => $order_id ) );
            if(count($product_data) != 0) {
                foreach ($product_data AS $pro){
                    //unset($pro['id']);
                    $update_id = App::save_data( $tblOrder, $pro );
                }
            }
            
            if ( !empty($tblOrderchild) ) {
                // restores product_order_child data
                $child_product_data = json_decode( $log_data['child_product_data'] , true );
                $this->order_model->deleteWhere( $tblOrderchild,array( 'order_id' => $order_id ) );
                if(count($child_product_data) != 0) {
                    foreach ($child_product_data AS $cpro){
                        //unset($cpro['id']);
                        $update_id = App::save_data( $tblOrderchild, $cpro );
                    }
                }
            }
            
            if ( !empty($tblOrderSubChild) ) {
                // restores product_order_subchild data
                $subchild_product_data = json_decode( $log_data['subchild_product_data'] , true );
                $this->order_model->deleteWhere( $tblOrderSubChild,array( 'order_id' => $order_id ) );
                if(count($subchild_product_data) != 0) {
                    foreach ($subchild_product_data AS $spro){
                        //unset($spro['id']);
                        $update_id = App::save_data( $tblOrderSubChild, $spro );
                    }
                }
            }
          
            
            // restores customer_payment data
            $payment_data = json_decode( $log_data['payment_data'] , true );
            $this->order_model->deleteWhere( 'customer_payment',array( 'order_id' => $order_id ) );
            if(count($payment_data) != 0) {
                foreach ($payment_data AS $pay){
                    unset($pay['id']);
                    $update_id = App::save_data( 'customer_payment', $pay );
                }
            }
            
            // restores expenses order
            $expenses_data = json_decode( $log_data['expenses_data'] , true );
            $this->order_model->deleteWhere( 'expenses',array( 'order_id' => $order_id ) );
            if(count($expenses_data) != 0) {
                foreach ($expenses_data AS $expense){
                    unset($expense['id']);
                    $update_id = App::save_data( 'expenses', $expense );
                }
            }
            
            // restores files order
            $files_data = json_decode( $log_data['files_data'] , true );
            $this->order_model->deleteWhere( 'files',array( 'field_id' => $order_id ) );
            if(count($files_data) != 0) {
                foreach ($files_data AS $file){
                    unset($file['file_id']);
                    $update_id = App::save_data( 'files', $file );
                }
            }
            
            $success = true;
            if( $success == true ){
                echo "TRUE";
            }else{
                echo "false";
            }
        }
    }
    /**
     * Display log dashboard
     * view log/dashboard.php
     */
    public function preview($id) {
        $data = array();
        $log_data =  (array) App::get_row_by_where('order_logs', array( 'id' => $id ) );
        $data['order_data'] = json_decode($log_data['order_data'], true);
        $data['expenses_data'] = json_decode( $log_data['expenses_data'] , true );
        $data['product_order'] = json_decode( $log_data['product_data'] , true );
        $data['product_order_child'] = json_decode( $log_data['child_product_data'] , true );
        $data['product_order_sub_child'] = json_decode( $log_data['subchild_product_data'] , true );
        $data['payment_data'] = json_decode( $log_data['payment_data'] , true );
        $data['files_data'] = json_decode( $log_data['files_data'] , true );
        $data['obj'] = $this;
        //pr($data['order_data']);
        $this->template->title('Order Preview'); 
        $this->template
                ->set_layout('preview')
                ->build('log/preview', $data);
    }

    public function getStyleName($style_id){
            $log_data =  (array) App::get_row_by_where('style', array( 'id' => $style_id ) );
            return $log_data['style_code'];
    }
    
    /*
    *   Functionality - delete order data
    *   Call from :  script
    */
    public function delete_order() {
        if( isset( $_POST['ids'] ) ){
            $ids = $_POST['ids'];
            //$data['orderDel'] = $this->order_model->Pkdelete_query( $ids, '');
            $data['orderDel'] = $this->db->delete('pk_order', array('id' => $ids));
            $data['porderDel'] = $this->db->delete('product_order', array('order_id' => $ids));
            $data['eorderDel'] = $this->db->delete('expenses', array('order_id' => $ids));
            $data['forderDel'] = $this->db->delete('files', array('field_id' => $ids));
            if($data == true) {
                echo "TRUE";
            }else{
                echo "FALSE";
                 }
        }
    }
    
    /*
    *   Functionality - orders edit section change product button get popup
    */
    public function change_product() {
        $data = array();
        $data['styles'] = (array) App::get_by_where('style', array('1'=>'1'));
        $this->load->view('modal/change_product', $data);
    }

    /*
    *   Functionality - orders edit section change product button get popup
    */
    public function styleByVendor() {
        $vID = $_POST['v_id'];
        $styles = $this->db->where( array( 'vendor_id' => $vID ) )->get('style')->result();
        $options = "<option value='0'>Select...</option>";
        if( $styles ){
            foreach( $styles as $style){
                $options .= "<option value='$style->id'>$style->style_name</option>";
            }
        }
        echo $options;
    }

    public function re_add_product() 
    {
        
        if ( isset( $_POST['Item_Code'] ) && isset( $_POST['style_id'] ) ) {
            $Item_Code = $_POST['Item_Code'];
            $style_id = $_POST['style_id'];
            $product_Id = $_POST['product_Id'];
          
            $pkexists = true;                    
            foreach ( $Item_Code AS $icode ) {
                $pkcount = $this->db->where( array( 'Item_Code ' => $icode, 'style_id' => $style_id ) )->count_all_results('pk_product');
               if ( $pkcount == 0 ) {
                    $pkexists = false;
                    $data['items'][] = $icode;
                }
            }
            
            if ( $pkexists == false ) {
               $data['status'] = 'error';
            }else{
                $data['status'] = 'success';
                $product_data = $this->db->or_where_in('Item_Code', $Item_Code)
                                        ->where( array( 'style_id' => $style_id ) )
                                        ->get('pk_product')->result();
                            
                foreach ( $product_data  AS $pr) {
                    $data['pros'][]  =   $pr->id;
                }
            }
            echo json_encode($data); 
        }
    }
    
    public function get_product_cat() {
        $styleID = $_POST['style_id'];
        
        $cat = $this->order_model->findWhere( 'pk_product', array( 'style_id' => $styleID ), TRUE, array( '*' ) );
        foreach( $cat as $key => $value ){
            $cat_value[] = $value['category'];  
        }
        
        $uni_value = array_unique( $cat_value );
        
        foreach( $uni_value as $key => $value ){
            $data[] = $this->order_model->findWhere( 'category', array( 'id' => $value,'cat_parent' => '0' ), FALSE, array(  'id','cat_name'  ) );
        }
        
        $options = "<option value='0'>Select...</option>";
        if( $data ){
            $c = 1;
            foreach( $data as $style){
                $options .= "<option value='".$style['id']."'>".$style['cat_name']."</option>";
                $c++;
            }
        }
        echo $options;
        
        //echo json_encode( $data );
        
    }
    
    public function get_pro_by_cat(){
        $cat_id = $_POST['catId'];
        $get_pro = $this->order_model->findWhere( 'pk_product', array( 'category' => $cat_id ), TRUE, array( 'id','Item_Code' ) );
        foreach( $get_pro as $key => $value ){
            $fltr = substr($value['Item_Code'],3,2);
            $nArr[$fltr] = 'Wall '.$fltr. ' H';
        }
        
        $options = "<option value='0'>Select...</option>";
        if( $nArr ){
            foreach( $nArr as $n){
                $val = substr($n,5,2);
                $options .= "<option value='".$val."'>".$n."</option>";
            }
        }
        echo $options;
    }
    
    public function get_pro_by_subcat(){
        if( isset( $_POST ) ){
            $get_pro = $this->order_model->findWhere( 'pk_product', array( 'category' => $_POST['c_id'],'style_id' => $_POST['st_id'] ), TRUE, array( 'id','Item_Code' ) );
            $options = '<div class="col-2">';
            $options .= '<div class="form-group">';
            $options .= "<option value='0'>Select...</option>";
            foreach( $get_pro as $key => $value ){
                $pro_sub = substr($value['Item_Code'],3,2);
                if( $pro_sub == $_POST['sub_cid'] ){
                     $options .= "<option value='".$value['id']."'>".$value['Item_Code']."</option>";
                }
            }
            
            $options .= '</div>';
            $options .= '</div>';
            
            echo $options;
        }
    }
    
    /* new work on category block */
    
    public function cat_by_style() {
        $styleID = $_POST['style_id'];
        
        $cat = $this->order_model->findWhere( 'pk_product', array( 'style_id' => $styleID ), TRUE, array( '*' ) );
        foreach( $cat as $key => $value ){
            $cat_value[] = $value['category'];  
        }
        
        $uni_value = array_unique( $cat_value );
        
        foreach( $uni_value as $key => $value ){
            $data[] = $this->order_model->findWhere( 'category', array( 'id' => $value,'cat_parent' => '0' ), FALSE, array(  'id','cat_name'  ) );
        }
        $options = "";
        if( $data ){
            foreach( $data as $style){
                    $options .= "<li class='catName' data-catid='".$style['id']."'>".$style['cat_name']."</li>";
            }
        }
        
        echo $options;
        
        //echo json_encode( $data );
        
    }
    
    public function cat_by_cat_product(){
        $cat_id   = $_POST['cat_id'];
        $cat_type = $_POST['cat_type'];
        $p_style  = $_POST['s_name'];
        
        if( $cat_type == 'product'){
            $resStatus['status'] = 'final';
            $dbRes = $this->order_model->findWhere( 'pk_product', array( 'sub_category_second' => $cat_id ,'style_id' => $p_style,), TRUE, array( '*', 'Item_Code as display_title') );
            
        
        }else{
            $resStatus['status'] = 'notfinal';
            $dbRes  = $this->order_model->findWhere( 'category', array( 'cat_parent' => $cat_id ), TRUE, array( 'id', 'cat_name as display_title'  ) );
            if(!$dbRes){
                $resStatus['status'] = 'final';
                $dbRes = $this->order_model->findWhere( 'pk_product', array( $cat_type => $cat_id ,'style_id' => $p_style), TRUE, array( '*', 'Item_Code as display_title' ) );            }
        }
        // echo $this->db->last_query();
        // pr($dbRes,1);
        $options = "";
        foreach( $dbRes as $key => $value ){
            if($resStatus['status'] == 'final'){
                //pr( $value );continue;
                if ( $value['is_sub_item_only'] != 'Y' ) {
                    $width = ''; if( !empty( $value['Width'] ) && ( $value['Width'] != 'NA' ) ) $width=$value['Width'].'W';
                    $height=''; if( !empty( $value['Height'] ) && ( $value['Height'] != 'NA' ) ) $height = $value['Height'].'x H';
                    $depth ='';  if( !empty( $value['Depth'] ) && ( $value['Depth'] !='NA' ) ) $depth = $value['Depth'].'x D';
                    $dN = "<b>".$value['display_title']."</b> - ". $value['Item_description'].' '.$width.$height.$depth;
                }
            }else{
                $dN = $value['display_title'];
            }
            $options .= "<li class='child_item' data-catid='".$value['id']."'>$dN</li>";
        }
        $resStatus['res'] = $options;
        echo json_encode($resStatus); 
    }
    
    function get_product_filter(){ 
        if( isset($_POST['search']) ){
            $search = $_POST['search'];
            $cond = array();
            if( !empty($_POST['vendor_id']) ){
                $cond = array_merge( $cond, array( "vendor_id" => $_POST['vendor_id'] ) );
            }
            if( !empty($_POST['style_id']) ){
                $cond = array_merge( $cond, array( "style_id" => $_POST['style_id'] ) );
            }
            $output = $this->db->like('Item_Name', $search)
                              ->where($cond)
                              ->get('pk_product')
                              ->result_array();
            $options = '';
            $width = ''; if( !empty( $row['Width'] ) && ( $row['Width'] != 'NA' ) ) $width=$row['Width'].'W';
                    $height=''; if( !empty( $row['Height'] ) && ( $row['Height'] != 'NA' ) ) $height = $row['Height'].'x H';
                    $depth ='';  if( !empty( $row['Depth'] ) && ( $row['Depth'] !='NA' ) ) $depth = $row['Depth'].'x D';
            foreach ($output as $row){
                $options .= "<li class='child_item' data-catid='".$row['id']."'><b>".$row['Item_Name'].$row['Item_description']." ".$width.$height.$depth."</b></li>";
            }
            $resStatus['status'] = 'final'; 
            $resStatus['res'] = $options;
            echo json_encode($resStatus);
        }
    }

    function get_child_product(){
        if (isset($_POST['item_code']) && isset($_POST['style_id'])) {
            
            $sql = "SELECT pro.*,pro.id as proid,sty.*,sty.id as styid FROM pk_product pro LEFT JOIN style sty on sty.id = pro.style_id  WHERE parent_id LIKE '%".$_POST['item_code']."%' AND style_id ='".$_POST['style_id']."'";

            $data = $this->order_model->custom_query($sql,false,'array');

            $option = "<option>Select Child</option>";
            foreach ($data as $key => $value) {
            
                $option .= "<option value='".$value['proid']."' cur-itemcode = '".$value['style_code']."' >".$value['style_code']."/".$value['Item_Code']."</option>";
 
            }

            echo $option;
        }
    }

    function assemble_update(){
        $id = $this->uri->segment(3);
        if( isset( $_POST['assemble_value'] ) ){
            $this->order_model->updateWhere('pk_order',array( 'id' => $_POST['order_id']), array( 'assemble_value' => $_POST['assemble_value'] ) );
            echo $this->db->last_query();
        }
    }
}