<?php
/*
    plex-gui.php

    WebGUI wrapper for the NAS4Free "Plex Media Server" add-on created by J.M Rivera
    (http://forums.nas4free.org/viewtopic.php?f=71&t=11049)
    
    Copyright (c) 2016 Andreas Schmidhuber
    All rights reserved.

	Portions of NAS4Free (http://www.nas4free.org).
	Copyright (c) 2012-2016 The NAS4Free Project <info@nas4free.org>.
	All rights reserved.

	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:

	1. Redistributions of source code must retain the above copyright notice, this
	   list of conditions and the following disclaimer.
	2. Redistributions in binary form must reproduce the above copyright notice,
	   this list of conditions and the following disclaimer in the documentation
	   and/or other materials provided with the distribution.

	THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
	ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
	WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
	DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
	ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
	(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
	LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
	ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
	(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
	SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

	The views and conclusions contained in the software and documentation are those
	of the authors and should not be interpreted as representing official policies,
	either expressed or implied, of the NAS4Free Project.
*/
require("auth.inc");
require("guiconfig.inc");

$pidfile = "/var/run/plex/plex.pid";

// Dummy standard message gettext calls for xgettext retrieval!!!
$dummy = gettext("The changes have been applied successfully.");

bindtextdomain("nas4free", "/usr/local/share/locale-plex");
$pgtitle = array(gettext("Extensions"), "Plex Media Server 0.4-alpha");

$i =0;
if ( is_array($config['rc']['postinit'] ) && is_array( $config['rc']['postinit']['cmd'] ) ) {
    for ($i; $i < count($config['rc']['postinit']['cmd']);) { if (preg_match('/plexinit/', $config['rc']['postinit']['cmd'][$i])) break; ++$i; }
}
$rootfolder = dirname($config['rc']['postinit']['cmd'][$i]);

if ($rootfolder == "") $input_errors[] = gettext("Extension installed with fault");
 
$plex_version = exec("plexinit -v");

if ($_POST) {
    if (isset($_POST['start']) && $_POST['start']) {
        $return_val = mwexec("plexinit -s", true);
        if ($return_val == 0) { $savemsg .= gettext("Plex Media Server started successfully"); }
        else { $input_errors[] = gettext("Plex Media Server startup failed"); }
    }

    if (isset($_POST['stop']) && $_POST['stop']) {
        $return_val = mwexec("plexinit -p && rm {$pidfile}", true);
        if ($return_val == 0) { $savemsg .= gettext("Plex Media Server stopped successfully"); }
        else { $input_errors[] = gettext("Plex Media Server stop failed"); }
    }

    if (isset($_POST['restart']) && $_POST['restart']) {
        $return_val = mwexec("plexinit -r", true);
        if ($return_val == 0) { $savemsg .= gettext("Plex Media Server restarted successfully"); }
        else { $input_errors[] = gettext("Plex Media Server restart failed"); }
    }

    if (isset($_POST['upgrade']) && $_POST['upgrade']) {
        $return_val = mwexec("plexinit -u", true);
        if ($return_val == 0) { $savemsg .= gettext("Plex Media Server upgraded successfully"); }
        else { $input_errors[] = gettext("Plex Media Server upgrade failed"); }
    }

    if (isset($_POST['remove']) && $_POST['remove']) {
        $return_val = mwexec("rm /usr/local/www/plex-gui.php && rm -R /usr/local/www/ext/plex-gui", true);
    	header("Location:index.php");
    }

    if (isset($_POST['uninstall']) && $_POST['uninstall']) {
        mwexec("plexinit -p", true);
        mwexec("rm /usr/local/www/plex-gui.php && rm -R /usr/local/www/ext/plex-gui", true);
        if (isset($_POST['plexdata'])) { $uninstall_cmd = "rm -R {$rootfolder}"; }
        else { $uninstall_cmd = "rm -R {$rootfolder}/plexmediaserver && rm -R {$rootfolder}/system && rm -R {$rootfolder}/gui && rm {$rootfolder}/plexinit {$rootfolder}/postinit"; }
        mwexec($uninstall_cmd, true);
        if (is_array($config['rc']['postinit']) && is_array($config['rc']['postinit']['cmd'])) {
    		for ($i = 0; $i < count($config['rc']['postinit']['cmd']);) {
        		if (preg_match('/plexinit/', $config['rc']['postinit']['cmd'][$i])) { unset($config['rc']['postinit']['cmd'][$i]); }
                ++$i;
    		}
    	}
    	write_config();
        header("Location:index.php");
    }
}	

function get_process_info() {
    global $pidfile;
    if (exec("ps acx | grep -f $pidfile")) { $state = '<a style=" background-color: #00ff00; ">&nbsp;&nbsp;<b>'.gettext("running").'</b>&nbsp;&nbsp;</a>'; }
    else { $state = '<a style=" background-color: #ff0000; ">&nbsp;&nbsp;<b>'.gettext("stopped").'</b>&nbsp;&nbsp;</a>'; }
	return ($state);
}

function get_process_pid() {
    global $pidfile;
    exec("cat $pidfile", $state); 
	return ($state[0]);
}

if (is_ajax()) {
	$procinfo['info'] = get_process_info();
	$procinfo['pid'] = get_process_pid();
	render_ajax($procinfo);
}

bindtextdomain("nas4free", "/usr/local/share/locale");
include("fbegin.inc");
bindtextdomain("nas4free", "/usr/local/share/locale-plex");
?>
<script type="text/javascript">//<![CDATA[
$(document).ready(function(){
	var gui = new GUI;
	gui.recall(0, 2000, 'plex-gui.php', null, function(data) {
		$('#procinfo').html(data.info);
		$('#procinfo_pid').html(data.pid);
	});
});
//]]>
</script>
<!-- The Spinner Elements -->
<?php include("ext/plex-gui/spinner.inc");?>
<script src="ext/plex-gui/spin.min.js"></script>
<!-- use: onsubmit="spinner()" within the form tag -->
<form action="plex-gui.php" method="post" name="iform" id="iform" onsubmit="spinner()">
    <table width="100%" border="0" cellpadding="0" cellspacing="0">
        <tr><td class="tabcont">
            <?php if (!empty($input_errors)) print_input_errors($input_errors);?>
            <?php if (!empty($savemsg)) print_info_box($savemsg);?>
            <table width="100%" border="0" cellpadding="6" cellspacing="0">
    			<?php html_titleline("Plex ".gettext("Information"));?>
                <?php html_text("installation_directory", gettext("Installation directory"), sprintf(gettext("The extension is installed in %s"), $rootfolder));?>
                <?php html_text("plex_version", gettext("Version"), $plex_version);?>
                <tr>
                    <td class="vncellt"><?=gettext("Status");?></td>
                    <td class="vtable"><span name="procinfo" id="procinfo"><?=get_process_info()?></span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;PID:&nbsp;<span name="procinfo_pid" id="procinfo_pid"><?=get_process_pid()?></span></td>
                </tr>
                <?php
                    $a_interface = get_interface_list();
                    $pconfig['if'] = key($a_interface);
                    $if = get_ifname($pconfig['if']);
                    $ipaddr = get_ipaddr($if);
                    $url = htmlspecialchars("http://{$ipaddr}:32400/web");
                    $text = "<a href='{$url}' target='_blank'>{$url}</a>";
                    html_text("url", gettext("WebGUI")." ".gettext("URL"), $text);
                ?>
            </table>
            <div id="remarks">
                <?php html_remark("note", gettext("Note"), gettext("Some remarks ... etc. etc."));?>
            </div>
            <div id="submit">
                <input name="start" type="submit" class="formbtn" title="<?=gettext("Start Plex Media Server");?>" value="<?=gettext("Start");?>" />
                <input name="stop" type="submit" class="formbtn" title="<?=gettext("Stop Plex Media Server");?>" value="<?=gettext("Stop");?>" />
                <input name="restart" type="submit" class="formbtn" title="<?=gettext("Restart Plex Media Server");?>" value="<?=gettext("Restart");?>" />
                <input name="upgrade" type="submit" class="formbtn" title="<?=gettext("Upgrade Plex Package");?>" value="<?=gettext("Update");?>" />
            </div>
            <table width="100%" border="0" cellpadding="6" cellspacing="0">
    			<?php html_separator();?>
                <?php html_titleline(gettext("Uninstall"));?>
                <?php html_checkbox("plexdata", gettext("Plexdata"), false, "<font color='red'>".gettext("Activate to delete user data (metadata and configuration) as well during the uninstall process.")."</font>", gettext("If not activated the directory $rootfolder/plexdata remains intact on the server."), false);?>
    			<?php html_separator();?>
            </table>
            <div id="submit1">
                <input name="remove" type="submit" class="formbtn" title="<?=gettext("Remove Plex add-on GUI");?>" value="<?=gettext("Remove");?>" onclick="return confirm('<?=gettext("Plex Media Server GUI will be removed, ready to proceed?");?>')" />
                <input name="uninstall" type="submit" class="formbtn" title="<?=gettext("Uninstall Plex Media Server");?>" value="<?=gettext("Uninstall");?>" onclick="return confirm('<?=gettext("Plex Media Server will be completely removed, ready to proceed?");?>')" />
            </div>
		</td></tr>
	</table>
	<?php include("formend.inc");?>
</form>
<?php include("fend.inc");?>
