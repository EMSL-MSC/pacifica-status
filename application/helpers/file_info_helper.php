<?php
/**
 * Pacifica
 *
 * Pacifica is an open-source data management framework designed
 * for the curation and storage of raw and processed scientific
 * data. It is based on the [CodeIgniter web framework](http://codeigniter.com).
 *
 *  The Pacifica-upload-status module provides an interface to
 *  the ingester status reporting backend, allowing users to view
 *  the current state of any uploads they may have performed, as
 *  well as enabling the download and retrieval of that data.
 *
 *  This file contains a number of common functions related to
 *  file info and handling.
 *
 * PHP version 5.5
 *
 * @package Pacifica-upload-status
 *
 * @author  Ken Auberry <kenneth.auberry@pnnl.gov>
 * @license BSD https://opensource.org/licenses/BSD-3-Clause
 *
 * @link http://github.com/EMSL-MSC/Pacifica-reporting
 */

/**
 *  Converts a numeric year quarter into starting/ending month
 *
 *  @param string $quarter_num numeric quarter of the year to use (1-4)
 *
 *  @return string (first_month)-(last_month) i.e. Jan-Mar
 *
 *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function quarter_to_range($quarter_num)
{
    $last_q = $quarter_num - 1;
    $this_q = $quarter_num;

    $first_month_num = $last_q * 3 + 1;
    $last_month_num = $this_q * 3;

    $first_month = date("M", mktime(0, 0, 0, $first_month_num, 1, 2012));
    $last_month = date("M", mktime(0, 0, 0, $last_month_num, 1, 2012));

    return "{$first_month}&ndash;{$last_month}";
}

/**
 *  Checks the current status of any given file within the archive
 *  system itself. Returns true if the file only resides currently
 *  in the tape archive, not the spinning disk cache
 *
 *  @param string $path filepath to check for current status
 *
 *  @return boolean does the file only currently exist on tape backup?
 *
 *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function is_file_on_tape($path)
{
    $on_tape = check_disk_stage($path, true);
    $on_tape = $on_tape == 0 ? true : false;
    return $on_tape;
}

/**
 *  With the right backend support, this function can
 *  determine the current status of any file within the
 *  system for purposes of fast retrieval
 *
 *  @param string  $path    filepath to check for current status
 *  @param boolean $numeric return a numeric value for true/false
 *                          or return a human readable string
 *
 *  @return string/integer
 *
 *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function check_disk_stage($path, $numeric = false)
{
    //fake it out until I get real support
    if ($numeric) {
        return 0;
    } else {
        return "on_tape";
    }
    $attr = exec("which attr");
    $status_attribute_name = "disk_stage_status";
    $attr_cmd = "{$attr} -g \"{$status_attribute_name}\" \"{$path}\"";
    $status_bit = exec($attr_cmd);
    $status_bit = intval($status_bit);

    $status = $status_bit == 0 ? "on_tape" : "on_disk";

    $status_bit = $numeric ? $status_bit : $status;

    return $status_bit;
}

/**
 *  Calculate the last modified date for the current file
 *
 *  @return datetime
 *
 *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function get_last_update()
{
    if (func_num_args() < 1) {
        return 0;
    }
    $dirs = func_get_args();
    $files = array();
    $accepted_subdirs = array('controllers','models','views','helpers');
    foreach ($dirs as $dir) {
        foreach ($accepted_subdirs as $subdir) {
            $fulldir = $dir . $subdir;
            $objects = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($fulldir),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            $files = array_merge($files, array_keys(iterator_to_array($objects, true)));
        }
    }
    $maxtimestamp = 0;
    $maxfilename = "";
    foreach ($files as $file) {
        $timestamp = filemtime($file);
        if ($timestamp > $maxtimestamp) {
            $maxtimestamp = $timestamp;
            $maxfilename = $file;
        }
        // $t = new Datetime();
        // $t->setTimestamp($timestamp);
        // echo "{$file} => {$t->format('Y-m-d H:i:s e')}<br />\n";
    }
    $d = new DateTime();
    $d->setTimestamp($maxtimestamp);
    return $d;
}
