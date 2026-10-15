<?php
if (get_option('siteurl') !== 'http://localhost:8099') exit(1);
function q($ok,$msg){if(!$ok){fwrite(STDERR,"FAIL $msg\n");exit(1);}echo "PASS $msg\n";}
function req($method,$route,$args=[]){$r=new WP_REST_Request($method,'/advertpreneur-connect/v1/'.$route);foreach($args as $k=>$v)$r->set_param($k,$v);$res=rest_do_request($r);return [$res->get_status(),$res->get_data()];}
wp_set_current_user(1);
q(class_exists('WooCommerce'),'WooCommerce active in disposable site');
// Remove only QA fixtures produced by this script from the isolated Docker test site.
foreach (get_posts(['post_type'=>'product','post_status'=>'any','numberposts'=>-1]) as $old) {
  if (strpos($old->post_title, 'Advertpreneur QA Disposable Product ')===0) wp_delete_post($old->ID,true);
}
$long='This private demonstration product is a sample entry used to exercise the catalog editing interface. It does not represent an item offered to customers and it must not be advertised as a physical product. Its description deliberately describes the test procedure rather than making unverifiable commercial claims. '
  .'For a legitimate launch, editors should replace placeholder copy with a clear explanation of the materials, measurements, customer audience and handling requirements. Any product performance statement should be checked against supplier documents before it reaches the public catalog. '
  .'The editorial checklist asks reviewers to confirm pricing, stock management, relevant taxonomy and image rights. A second reviewer can open the draft in the standard WordPress editor, check the description for ambiguity and inspect the product image on both desktop and mobile screen sizes. '
  .'Before a product is published, confirm that its name does not conflict with a current listing and that customers can easily understand the intended use. This fixture has no customer orders, payment details or shipping promises, since its only purpose is a reproducible authorization and quality gate test.';
$name='Advertpreneur QA Disposable Product '.date('His');
[$status,$created]=req('POST','products',['name'=>$name,'description'=>'<h2>Product use and guidance</h2><p>'.$long.'</p>','short_description'=>'Staging fixture','regular_price'=>'12.50','type'=>'simple']);
q($status<300&&!empty($created['product']['id']),'product created in draft');
$id=(int)$created['product']['id'];q($created['product']['status']==='draft','product status draft');
[$s,$duplicate]=req('POST','products',['name'=>$name]);q($s===409,'duplicate product name rejected');
[$s,$blocked]=req('POST','products/'.$id.'/publish',[]);q(in_array($s,[409,422]),'product publish blocked without quality attestation');
$contributor=wp_insert_user(['user_login'=>'adpc_qa_woo_reader_'.time(),'user_pass'=>'TestOnlyLocal','user_email'=>'woo'.time().'@localhost.invalid','role'=>'contributor']);q(!is_wp_error($contributor),'restricted user created');
wp_set_current_user($contributor);
[$s,$denied]=req('PATCH','products/'.$id,['regular_price'=>'1.00']);q($s===403,'contributor denied product price changes');
wp_set_current_user(1);
[$s,$check]=req('POST','quality/check',['post_id'=>$id]);if ($s>=300||empty($check['pass'])) echo 'QUALITY_DIAGNOSTIC '.json_encode(['status'=>$s,'keys'=>array_keys($check),'context'=>$check['structural']['context']??null,'words'=>$check['structural']['word_count']??null,'flags'=>$check['structural']['flags']??null,'overlap'=>$check['site_overlap']['flags']??null])."\n"; q($s<300&&!empty($check['pass']),'WooCommerce product quality checks pass');
$report=['passed'=>true,'site_overlap_checked'=>true,'repetition_checked'=>true,'misleading_claims_checked'=>true,'source_reviewed'=>true,'content_sha256'=>$check['content_sha256']];
[$s,$att]=req('POST','quality/attest',['post_id'=>$id,'report'=>$report]);q($s<300&&!empty($att['ok']),'product quality attested');
[$s,$edited]=req('PATCH','products/'.$id,['description'=>'<h2>Updated guidance</h2><p>'.$long.'</p>']);q($s<300,'product content updated');
[$s,$stale]=req('POST','products/'.$id.'/publish');q(in_array($s,[409,422]),'product content update invalidates quality gate');
[$s,$check]=req('POST','quality/check',['post_id'=>$id]);
$report['content_sha256']=$check['content_sha256'];
[$s,$att]=req('POST','quality/attest',['post_id'=>$id,'report'=>$report]);q($s<300&&!empty($att['ok']),'product reattestation passes');
[$s,$published]=req('POST','products/'.$id.'/publish',[]);q($s<300 && ($published['product']['status']??'')==='publish','authorized WooCommerce product published on isolated staging');
wp_delete_post($id,true);
echo "WOOCOMMERCE_QA_PASSED (QA fixture cleaned up)\n";
