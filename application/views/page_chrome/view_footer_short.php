<?php
// @codingStandardsIgnoreFile
?>
<footer class="short">
    <section id="contact_info" class="contact_info">
        <?php $git_hash_string = !empty($this->git_hash) ? " [{$this->git_hash}]" : ""; ?>
        <div id="last_update_timestamp" style="">Version <?php echo $this->application_version ?><?php echo $git_hash_string ?> / Updated <?php echo $this->last_update_time->format('n/j/Y g:i a') ?></div>
    </section>
    <section id="pager" class="pager_section">
        <?php $this->load->view("page_chrome/pager_block_insert.html"); ?>
    </section>
</footer>
