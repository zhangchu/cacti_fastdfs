<?php

/*
      'FASTDFS_status'                    =>  'oq',
      'FASTDFS_total_storage'             =>  'or',
      'FASTDFS_free_storage'              =>  'os',
      'FASTDFS_total_upload_count'        =>  'ot',
      'FASTDFS_success_upload_count'      =>  'ou',
      'FASTDFS_total_append_count'        =>  'ov',
      'FASTDFS_success_append_count'      =>  'ow',
      'FASTDFS_total_modify_count'        =>  'ox',
      'FASTDFS_success_modify_count'      =>  'oy',
      'FASTDFS_total_truncate_count'      =>  'oz',
      'FASTDFS_success_truncate_count'    =>  'pg',
      'FASTDFS_total_set_meta_count'      =>  'ph',
      'FASTDFS_success_set_meta_count'    =>  'pi',
      'FASTDFS_total_delete_count'        =>  'pj',
      'FASTDFS_success_delete_count'      =>  'pk',
      'FASTDFS_total_download_count'      =>  'pl',
      'FASTDFS_success_download_count'    =>  'pm',
      'FASTDFS_total_get_meta_count'      =>  'pn',
      'FASTDFS_success_get_meta_count'    =>  'po',
      'FASTDFS_total_create_link_count'   =>  'pp',
      'FASTDFS_success_create_link_count' =>  'pq',
      'FASTDFS_total_delete_link_count'   =>  'pr',
      'FASTDFS_success_delete_link_count' =>  'ps',
      'FASTDFS_total_upload_bytes'        =>  'pt',
      'FASTDFS_success_upload_bytes'      =>  'pu',
      'FASTDFS_total_append_bytes'        =>  'pv',
      'FASTDFS_success_append_bytes'      =>  'pw',
      'FASTDFS_total_modify_bytes'        =>  'px',
      'FASTDFS_success_modify_bytes'      =>  'py',
      'FASTDFS_stotal_download_bytes'     =>  'pz',
      'FASTDFS_success_download_bytes'    =>  'qg',
      'FASTDFS_total_sync_in_bytes'       =>  'qh',
      'FASTDFS_success_sync_in_bytes'     =>  'qi',
      'FASTDFS_total_sync_out_bytes'      =>  'qj',
      'FASTDFS_success_sync_out_bytes'    =>  'qk',
      'FASTDFS_total_file_open_count'     =>  'ql',
      'FASTDFS_success_file_open_count'   =>  'qm',
      'FASTDFS_total_file_read_count'     =>  'qn',
      'FASTDFS_success_file_read_count'   =>  'qo',
      'FASTDFS_total_file_write_count'    =>  'qp',
      'FASTDFS_success_file_write_count'  =>  'qq',
*/

function fastdfs_cachefile ( $options ) {
	return $options['host'].'_fastdfs';
}

function fastdfs_cmdline ( $options ) {
	return '/usr/local/fastdfs/client/fdfs_monitor /etc/fdfs/client.conf';
}

function fastdfs_get ( $options ) {
	global $cache_dir;
	
	$tmpfile = $cache_dir.'/fastdfs_results.txt';
	if (!is_file($tmpfile) || ((time() - filemtime($tmpfile)) > 300)) {
		system('/usr/local/fastdfs/client/fdfs_monitor /etc/fdfs/client.conf > '.$tmpfile.' 2>&1');
	}
	return file_get_contents($tmpfile);
}

function fastdfs_parse ( $options, $output ) {
	static $s_statuslist = array(
		'INIT' => 1,
		'WAIT_SYNC' => 2,
		'SYNCING' => 3,
		'DELETED' => 4,
		'OFFLINE' => 5,
		'ONLINE' => 6,
		'ACTIVE' => 7,
	);
	static $s_countlist = array(
		'total_upload_count',
		'success_upload_count',
		'total_append_count',
		'success_append_count',
		'total_modify_count',
		'success_modify_count',
		'total_truncate_count',
		'success_truncate_count',
		'total_set_meta_count',
		'success_set_meta_count',
		'total_delete_count',
		'success_delete_count',
		'total_download_count',
		'success_download_count',
		'total_get_meta_count',
		'success_get_meta_count',
		'total_create_link_count',
		'success_create_link_count',
		'total_delete_link_count',
		'success_delete_link_count',
		'total_upload_bytes',
		'success_upload_bytes',
		'total_append_bytes',
		'success_append_bytes',
		'total_modify_bytes',
		'success_modify_bytes',
		'stotal_download_bytes',
		'success_download_bytes',
		'total_sync_in_bytes',
		'success_sync_in_bytes',
		'total_sync_out_bytes',
		'success_sync_out_bytes',
		'total_file_open_count',
		'success_file_open_count',
		'total_file_read_count',
		'success_file_read_count',
		'total_file_write_count',
		'success_file_write_count',
	);

	$result = array();
	$lines = explode("\n", $output);
	$startline = 'id = '.$options['host'];

	$flag = 0;  //0,1,2 -- ready,start,end
	foreach ($lines as $line) {
		$line = trim($line);
		if (0 == $flag) {
			if ($line == $startline) {
				$flag = 1;
			}
		} else if (1 == $flag) {
			if ('id =' == substr($line, 0, 4)) {
				$flag = 2;
				break;
			}
			list($name, $value) = explode(' = ', $line);
			if ('ip_addr' == $name) {
				list($ip, $status) = explode('  ', $value);
				$result['FASTDFS_status'] = isset($s_statuslist[$status]) ? $s_statuslist[$status] : 0;
			} else if ('total storage' == $name) {
				$result['FASTDFS_total_storage'] = intval($value) * 1024 * 1024;
			} else if ('free storage' == $name) {
				$result['FASTDFS_free_storage'] = intval($value) * 1024 * 1024;
			} else if (in_array($name, $s_countlist)) {
				$result['FASTDFS_'.$name] = intval($value);
			}
		}
	}
	
	return $result;
}
