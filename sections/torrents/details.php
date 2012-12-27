<?
function compare($X, $Y){
	return($Y['score'] - $X['score']);
}
header('Access-Control-Allow-Origin: *');

define('MAX_PERS_COLLAGES', 3); // How many personal collages should be shown by default
define('MAX_COLLAGES', 5);      // How many normal collages should be shown by default

include(SERVER_ROOT.'/sections/bookmarks/functions.php'); // has_bookmarked()
include(SERVER_ROOT.'/classes/class_text.php');
include(SERVER_ROOT.'/classes/class_image_tools.php');

$Text = NEW TEXT;

$GroupID=ceil($_GET['id']);
if(!empty($_GET['revisionid']) && is_number($_GET['revisionid'])) {
	$RevisionID = $_GET['revisionid'];
} else { $RevisionID = 0; }

include(SERVER_ROOT.'/sections/torrents/functions.php');
$TorrentCache = get_group_info($GroupID, true, $RevisionID);
$TorrentDetails = $TorrentCache[0];
$TorrentList = $TorrentCache[1];

// Group details
list($WikiBody, $WikiImage, $GroupID, $GroupName, $GroupYear,
	$GroupRecordLabel, $GroupCatalogueNumber, $ReleaseType, $GroupCategoryID,
	$GroupTime, $GroupVanityHouse, $TorrentTags, $TorrentTagIDs, $TorrentTagUserIDs,
	$TagPositiveVotes, $TagNegativeVotes, $GroupFlags) = array_values($TorrentDetails);

$DisplayName=$GroupName;
$AltName=$GroupName; // Goes in the alt text of the image
$Title=$GroupName; // goes in <title>
$WikiBody = $Text->full_format($WikiBody);

$Artists = Artists::get_artist($GroupID);

if($Artists) {
	$DisplayName = '<span dir="ltr">'.Artists::display_artists($Artists, true).$DisplayName.'</span>';
	$AltName = display_str(Artists::display_artists($Artists, false)).$AltName;
	$Title = $AltName;
}

if($GroupYear>0) {
	$DisplayName.=' ['.$GroupYear.']';
	$AltName.=' ['.$GroupYear.']';
	$Title.= ' ['.$GroupYear.']';
}
if($GroupVanityHouse){
	$DisplayName.=' [Vanity House]';
	$AltName.=' [Vanity House]';
}
if($GroupCategoryID == 1) {
	$DisplayName.=' ['.$ReleaseTypes[$ReleaseType].']';
	$AltName.=' ['.$ReleaseTypes[$ReleaseType].']';
}

$Tags = array();
if ($TorrentTags != '') {
	$TorrentTags=explode('|',$TorrentTags);
	$TorrentTagIDs=explode('|',$TorrentTagIDs);
	$TorrentTagUserIDs=explode('|',$TorrentTagUserIDs);
	$TagPositiveVotes=explode('|',$TagPositiveVotes);
	$TagNegativeVotes=explode('|',$TagNegativeVotes);
	
	foreach ($TorrentTags as $TagKey => $TagName) {
		$Tags[$TagKey]['name'] = $TagName;
		$Tags[$TagKey]['score'] = ($TagPositiveVotes[$TagKey] - $TagNegativeVotes[$TagKey]);
		$Tags[$TagKey]['id']=$TorrentTagIDs[$TagKey];
		$Tags[$TagKey]['userid']=$TorrentTagUserIDs[$TagKey];
	}
	uasort($Tags, 'compare');
}

/*if (check_perms('site_debug')) {
	print_r($TorrentTags);
	print_r($Tags);
	print_r($TorrentTagUserIDs);
	die();
}*/

// Start output
View::show_header($Title,'jquery,browse,comments,torrent,bbcode');
?>
<div class="thin">
	<div class="header">
		<h2><?=$DisplayName?></h2>
		<div class="linkbox">
<?	if(check_perms('site_edit_wiki')) { ?>
			<a href="torrents.php?action=editgroup&amp;groupid=<?=$GroupID?>">[Edit description]</a>
<?	} ?>
			<a href="torrents.php?action=history&amp;groupid=<?=$GroupID?>">[View history]</a>
<?	if($RevisionID && check_perms('site_edit_wiki')) { ?>
			<a href="/torrents.php?action=revert&amp;groupid=<?=$GroupID ?>&amp;revisionid=<?=$RevisionID ?>&amp;auth=<?=$LoggedUser['AuthKey']?>">[Revert to this revision]</a>
<?	}
	if(has_bookmarked('torrent', $GroupID)) {
?>
			<a href="#" id="bookmarklink_torrent_<?=$GroupID?>" onclick="Unbookmark('torrent', <?=$GroupID?>,'[Bookmark]');return false;">[Remove bookmark]</a>
<?	} else { ?>
			<a href="#" id="bookmarklink_torrent_<?=$GroupID?>" onclick="Bookmark('torrent', <?=$GroupID?>,'[Remove bookmark]');return false;">[Bookmark]</a>
<?	}
	if($Categories[$GroupCategoryID-1] == 'Music') { ?>
			<a href="upload.php?groupid=<?=$GroupID?>">[Add format]</a>
<?	} 
	if(check_perms('site_submit_requests')) { ?>
			<a href="requests.php?action=new&amp;groupid=<?=$GroupID?>">[Request format]</a>
<?	}?>
			<a href="torrents.php?action=grouplog&amp;groupid=<?=$GroupID?>">[View log]</a>
		</div>
	</div>
	<div class="sidebar">
		<div class="box box_image box_image_albumart box_albumart"><!-- .box_albumart deprecated -->
			<div class="head"><strong>Cover</strong></div>
<?
if ($WikiImage!="") {
	if(check_perms('site_proxy_images')) {
		$WikiImage = 'http'.($SSL?'s':'').'://'.SITE_URL.'/image.php?i='.urlencode($WikiImage);
	}
?>
			<p align="center"><img style="max-width: 220px;" src="<?=to_thumbnail($WikiImage)?>" alt="<?=$AltName?>" onclick="lightbox.init('<?=$WikiImage?>',220);" /></p>
<?
} else {
?>
			<p align="center"><img src="<?=STATIC_SERVER?>common/noartwork/<?=$CategoryIcons[$GroupCategoryID-1]?>" alt="<?=$Categories[$GroupCategoryID-1]?>" title="<?=$Categories[$GroupCategoryID-1]?>" width="220" height="220" border="0" /></p>
<?
}
?>
		</div>
<?
if($Categories[$GroupCategoryID-1] == 'Music') {
	$ShownWith = false;
?>
		<div class="box box_artists">
			<div class="head"><strong>Artists</strong>
			<?=(check_perms('torrents_edit')) ? '<span style="float:right;" class="edit_artists"><a onclick="ArtistManager(); return false;" href="#">[Edit]</a></span>' : ''?>
			</div>
			<ul class="stats nobullet" id="artist_list">
<?	if(!empty($Artists[4]) && count($Artists[4]) > 0) {
		print '				<li class="artists_composers"><strong class="artists_label">Composers:</strong></li>';
		foreach($Artists[4] as $Artist) {
?>
				<li class="artists_composers">
					<?=Artists::display_artist($Artist).' &lrm;'?>
<?			if(check_perms('torrents_edit')){
				$DB->query("SELECT AliasID FROM artists_alias WHERE ArtistID = ".$Artist['id']." AND ArtistID != AliasID AND Name = '".db_string($Artist['name'])."'");
				list($AliasID) = $DB->next_record();
				if (empty($AliasID)) {
					$AliasID = $Artist['id'];
				}
?>
				(<?=$AliasID?>)&nbsp;
					<span class="remove remove_artist"><a href="javascript:void(0);" onclick="ajax.get('torrents.php?action=delete_alias&amp;auth=' + authkey + '&amp;groupid=<?=$GroupID?>&amp;artistid=<?=$Artist['id']?>&amp;importance=4');this.parentNode.parentNode.style.display = 'none';" title="Remove artist">[X]</a></span>
<?			} ?>
				</li>
<?		}
	}
	if (!empty($Artists[6]) && count($Artists[6]) > 0) {
		print '				<li class="artists_dj"><strong class="artists_label">DJ / Compiler:</strong></li>';
		foreach($Artists[6] as $Artist) {
?>
				<li class="artists_dj">
					<?=Artists::display_artist($Artist).' &lrm;'?>
<?			if(check_perms('torrents_edit')){
				$DB->query("SELECT AliasID FROM artists_alias WHERE ArtistID = ".$Artist['id']." AND ArtistID != AliasID AND Name = '".db_string($Artist['name'])."'");
					list($AliasID) = $DB->next_record();
					if (empty($AliasID)) {
						$AliasID = $Artist['id'];
					}
?>
				(<?=$AliasID?>)&nbsp;
					<span class="remove remove_artist"><a href="javascript:void(0);" onclick="ajax.get('torrents.php?action=delete_alias&amp;auth=' + authkey + '&amp;groupid=<?=$GroupID?>&amp;artistid=<?=$Artist['id']?>&amp;importance=6');this.parentNode.parentNode.style.display = 'none';" title="Remove artist">[X]</a></span>
<?			} ?>
				</li>
<?
		}
	}
	if ((count($Artists[6]) > 0) && (count($Artists[1]) > 0)) {
		print '				<li class="artists_main"><strong class="artists_label">Artists:</strong></li>';
	} elseif ((count($Artists[4]) > 0) && (count($Artists[1]) > 0)) {
		print '				<li class="artists_main"><strong class="artists_label">Performers:</strong></li>';
	}
	foreach($Artists[1] as $Artist) {
?>
				<li class="artist_main">
					<?=Artists::display_artist($Artist).' &lrm;'?>
<?		if(check_perms('torrents_edit')){
			$AliasID = $Artist['aliasid'];
			if (empty($AliasID)) {
				$AliasID = $Artist['id'];
			}
?>
			(<?=$AliasID?>)&nbsp;
				<span class="remove remove_artist"><a href="javascript:void(0);" onclick="ajax.get('torrents.php?action=delete_alias&amp;auth=' + authkey + '&amp;groupid=<?=$GroupID?>&amp;artistid=<?=$Artist['id']?>&amp;importance=1');this.parentNode.parentNode.style.display = 'none';" title="Remove artist">[X]</a></span>
<?		} ?>
				</li>
<?
	}
	if(!empty($Artists[2]) && count($Artists[2]) > 0) {
		print '				<li class="artists_with"><strong class="artists_label">With:</strong></li>';
		foreach($Artists[2] as $Artist) {
?>
				<li class="artist_guest">
					<?=Artists::display_artist($Artist).' &lrm;'?>
<?			if(check_perms('torrents_edit')){
				$DB->query("SELECT AliasID FROM artists_alias WHERE ArtistID = ".$Artist['id']." AND ArtistID != AliasID AND Name = '".db_string($Artist['name'])."'");
				list($AliasID) = $DB->next_record();
				if (empty($AliasID)) {
					$AliasID = $Artist['id'];
				}
?>
				(<?=$AliasID?>)&nbsp;
					<span class="remove remove_artist"><a href="javascript:void(0);" onclick="ajax.get('torrents.php?action=delete_alias&amp;auth=' + authkey + '&amp;groupid=<?=$GroupID?>&amp;artistid=<?=$Artist['id']?>&amp;importance=2');this.parentNode.parentNode.style.display = 'none';" title="Remove artist">[X]</a></span>
<?			} ?>
				</li>
<?
		}
	}
	if(!empty($Artists[5]) && count($Artists[5]) > 0) {
		print '				<li class="artists_conductors"><strong class="artists_label">Conducted by:</strong></li>';
		foreach($Artists[5] as $Artist) {
?>
				<li class="artists_conductors">
					<?=Artists::display_artist($Artist).' &lrm;'?>
<?			if(check_perms('torrents_edit')){
				$DB->query("SELECT AliasID FROM artists_alias WHERE ArtistID = ".$Artist['id']." AND ArtistID != AliasID AND Name = '".db_string($Artist['name'])."'");
				list($AliasID) = $DB->next_record();
				if (empty($AliasID)) {
					$AliasID = $Artist['id'];
				}
?>
				(<?=$AliasID?>)&nbsp;
					<span class="remove remove_artist"><a href="javascript:void(0);" onclick="ajax.get('torrents.php?action=delete_alias&amp;auth=' + authkey + '&amp;groupid=<?=$GroupID?>&amp;artistid=<?=$Artist['id']?>&amp;importance=5');this.parentNode.parentNode.style.display = 'none';" title="Remove conductor">[X]</a></span>
<?			} ?>
				</li>
<?
		}
	}
	if (!empty($Artists[3]) && count($Artists[3]) > 0) {
		print '				<li class="artists_remix"><strong class="artists_label">Remixed by:</strong></li>';
		foreach($Artists[3] as $Artist) {
?>
				<li class="artists_remix">
					<?=Artists::display_artist($Artist).' &lrm;'?>
<?		      if(check_perms('torrents_edit')){
			        $DB->query("SELECT AliasID FROM artists_alias WHERE ArtistID = ".$Artist['id']." AND ArtistID != AliasID AND Name = '".db_string($Artist['name'])."'");
                                list($AliasID) = $DB->next_record();
                                if (empty($AliasID)) {
                                        $AliasID = $Artist['id'];
                                }
?>
				(<?=$AliasID?>)&nbsp;
					<span class="remove remove_artist"><a href="javascript:void(0);" onclick="ajax.get('torrents.php?action=delete_alias&amp;auth=' + authkey + '&amp;groupid=<?=$GroupID?>&amp;artistid=<?=$Artist['id']?>&amp;importance=3');this.parentNode.parentNode.style.display = 'none';" title="Remove artist">[X]</a></span>
<?		      } ?>
				</li>
<?
		}
	}
	if (!empty($Artists[7]) && count($Artists[7]) > 0) {
		print '				<li class="artists_producer"><strong class="artists_label">Produced by:</strong></li>';
		foreach($Artists[7] as $Artist) {
?>
				<li class="artists_producer">
					<?=Artists::display_artist($Artist).' &lrm;'?>
<?		      if(check_perms('torrents_edit')){
			        $DB->query("SELECT AliasID FROM artists_alias WHERE ArtistID = ".$Artist['id']." AND ArtistID != AliasID AND Name = '".db_string($Artist['name'])."'");
                                list($AliasID) = $DB->next_record();
                                if (empty($AliasID)) {
                                        $AliasID = $Artist['id'];
                                }
?>
				(<?=$AliasID?>)&nbsp;
					<span class="remove remove_artist"><a href="javascript:void(0);" onclick="ajax.get('torrents.php?action=delete_alias&amp;auth=' + authkey + '&amp;groupid=<?=$GroupID?>&amp;artistid=<?=$Artist['id']?>&amp;importance=7');this.parentNode.parentNode.style.display = 'none';" title="Remove producer">[X]</a></span>
<?		      } ?>
				</li>
<?
		}
	}
?>
			</ul>
		</div>
<? 
		if(check_perms('torrents_add_artist')) { ?>
		<div class="box box_addartists">
			<div class="head"><strong>Add artist</strong><span style="float:right;" class="additional_add_artist"><a onclick="AddArtistField(); return false;" href="#">[+]</a></span></div>
			<div class="body">
				<form class="add_form" name="artists" action="torrents.php" method="post">
					<div id="AddArtists">
						<input type="hidden" name="action" value="add_alias" />
						<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
						<input type="hidden" name="groupid" value="<?=$GroupID?>" />
						<input type="text" name="aliasname[]" size="17" />
						<select name="importance[]">
							<option value="1">Main</option>
							<option value="2">Guest</option>
							<option value="4">Composer</option>
							<option value="5">Conductor</option>
							<option value="6">DJ / Compiler</option>
							<option value="3">Remixer</option>
							<option value="7">Producer</option>
						</select>
					</div>
					<input type="submit" value="Add" />
				</form>
			</div>
		</div>
<?		}
	}
include(SERVER_ROOT.'/sections/torrents/vote_ranks.php');
include(SERVER_ROOT.'/sections/torrents/vote.php');
?>
		<div class="box box_tags">
			<div class="head"><strong>Tags</strong></div>
<?
if(count($Tags) > 0) {
?>
			<ul class="stats nobullet">
<?
	foreach($Tags as $TagKey=>$Tag) {
			
?>
				<li>
					<a href="torrents.php?taglist=<?=$Tag['name']?>" style="float:left; display:block;"><?=display_str($Tag['name'])?></a>
					<div style="float:right; display:block; letter-spacing: -1px;" class="edit_tags_votes">
					<a href="torrents.php?action=vote_tag&amp;way=down&amp;groupid=<?=$GroupID?>&amp;tagid=<?=$Tag['id']?>&amp;auth=<?=$LoggedUser['AuthKey']?>" style="font-family: monospace;" title="Vote this tag down" class="vote_tag_down">[-]</a>
					<?=$Tag['score']?>
					<a href="torrents.php?action=vote_tag&amp;way=up&amp;groupid=<?=$GroupID?>&amp;tagid=<?=$Tag['id']?>&amp;auth=<?=$LoggedUser['AuthKey']?>" style="font-family: monospace;" title="Vote this tag up" class="vote_tag_up">[+]</a>
<?		if(check_perms('users_warn')){ ?>
					<a href="user.php?id=<?=$Tag['userid']?>" title="View the profile of the user that added this tag" class="view_tag_user">[U]</a>
<?		} ?>
<?		if(empty($LoggedUser['DisableTagging']) && check_perms('site_delete_tag')){ ?>
					<span class="remove remove_tag"><a href="torrents.php?action=delete_tag&amp;groupid=<?=$GroupID?>&amp;tagid=<?=$Tag['id']?>&amp;auth=<?=$LoggedUser['AuthKey']?>" title="Remove tag">[X]</a></span>
<?		} ?>
					</div>
					<br style="clear:both" />
				</li>
<?
	}
?>
			</ul>
<?
} else { // The "no tags to display" message was wrapped in <ul> tags to pad the text.
?>
			<ul><li>There are no tags to display.</li></ul>
<?
}
?>
		</div>
<?
if (empty($LoggedUser['DisableTagging'])) {
?>
		<div class="box box_addtag">
			<div class="head"><strong>Add tag</strong></div>
			<div class="body">
				<form class="add_form" name="tags" action="torrents.php" method="post">
					<input type="hidden" name="action" value="add_tag" />
					<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
					<input type="hidden" name="groupid" value="<?=$GroupID?>" />
					<input type="text" name="tagname" size="20" />
					<input type="submit" value="+" />
				</form>
				<br /><br />
				<strong><a href="rules.php?p=tag">Tagging rules</a></strong>
			</div>
		</div>
<?
}
?>
	</div>
	<div class="main_column">
		<table class="torrent_table details<?=$GroupFlags['IsSnatched'] ? ' snatched' : ''?>" id="torrent_details">
			<tr class="colhead_dark">
				<td width="80%"><strong>Torrents</strong></td>
				<td><strong>Size</strong></td>
				<td class="sign"><img src="static/styles/<?=$LoggedUser['StyleName'] ?>/images/snatched.png" alt="Snatches" title="Snatches" /></td>
				<td class="sign"><img src="static/styles/<?=$LoggedUser['StyleName'] ?>/images/seeders.png" alt="Seeders" title="Seeders" /></td>
				<td class="sign"><img src="static/styles/<?=$LoggedUser['StyleName'] ?>/images/leechers.png" alt="Leechers" title="Leechers" /></td>
			</tr>
<?

function filelist($Str) {
	return "</td><td>".Format::get_size($Str[1])."</td></tr>";
}

$LastRemasterYear = '-';
$LastRemasterTitle = '';
$LastRemasterRecordLabel = '';
$LastRemasterCatalogueNumber = '';

$EditionID = 0;

foreach ($TorrentList as $Torrent) {
		//t.ID,	t.Media, t.Format, t.Encoding, t.Remastered, t.RemasterYear,
		//t.RemasterTitle, t.RemasterRecordLabel, t.RemasterCatalogueNumber, t.Scene,
		//t.HasLog, t.HasCue, t.LogScore, t.FileCount, t.Size, t.Seeders, t.Leechers,
		//t.Snatched, t.FreeTorrent, t.Time, t.Description, t.FileList,
		//t.FilePath, t.UserID, t.last_action, HEX(t.info_hash), (bad tags), (bad folders), (bad filenames),
		//(cassette approved), (lossy master approved), (lossy web approved), t.LastReseedRequest,
		//LogInDB, (has file), Torrents::torrent_properties()
	list($TorrentID, $Media, $Format, $Encoding, $Remastered, $RemasterYear,
		$RemasterTitle, $RemasterRecordLabel, $RemasterCatalogueNumber, $Scene,
		$HasLog, $HasCue, $LogScore, $FileCount, $Size, $Seeders, $Leechers,
		$Snatched, $FreeTorrent, $TorrentTime, $Description, $FileList,
		$FilePath, $UserID, $LastActive, $InfoHash, $BadTags, $BadFolders, $BadFiles,
		$CassetteApproved, $LossymasterApproved, $LossywebApproved, $LastReseedRequest,
		$LogInDB, $HasFile, $PersonalFL, $IsSnatched) = array_values($Torrent);

	if($Remastered && !$RemasterYear) {
		$FirstUnknown = !isset($FirstUnknown);
	}

	$Reported = false;
	unset($ReportedTimes);
	$Reports = $Cache->get_value('reports_torrent_'.$TorrentID);
	if($Reports === false) {
		$DB->query("SELECT r.ID,
				r.ReporterID,
				r.Type,
				r.UserComment,
				r.ReportedTime
			FROM reportsv2 AS r
			WHERE TorrentID = $TorrentID
				AND Type != 'edited'
				AND Status != 'Resolved'");
		$Reports = $DB->to_array();
		$Cache->cache_value('reports_torrent_'.$TorrentID, $Reports, 0);
	}
	if(count($Reports) > 0) {
		$Reported = true;
		include(SERVER_ROOT.'/sections/reportsv2/array.php');
		$ReportInfo = '<table><tr class="colhead_dark" style="font-weight: bold;"><td>This torrent has '.count($Reports).' active '.(count($Reports) > 1 ? "reports" : "report").':</td></tr>';

		foreach($Reports as $Report) {
			list($ReportID, $ReporterID, $ReportType, $ReportReason, $ReportedTime) = $Report;

			$Reporter = Users::user_info($ReporterID);
			$ReporterName = $Reporter['Username'];

			if (array_key_exists($ReportType, $Types[$GroupCategoryID])) {
				$ReportType = $Types[$GroupCategoryID][$ReportType];
			} else if(array_key_exists($ReportType,$Types['master'])) {
				$ReportType = $Types['master'][$ReportType];
			} else {
				//There was a type but it wasn't an option!
				$ReportType = $Types['master']['other'];
			}
			$ReportInfo .= "<tr><td>".(check_perms('admin_reports') ? "<a href='user.php?id=$ReporterID'>$ReporterName</a> <a href='reportsv2.php?view=report&amp;id=$ReportID'>reported it</a> " : "Someone reported it ").time_diff($ReportedTime,2,true,true)." for the reason '".$ReportType['title']."':";
			$ReportInfo .= "<blockquote>".$Text->full_format($ReportReason)."</blockquote></td></tr>";
		}
		$ReportInfo .= "</table>";
	}
	
	$CanEdit = (check_perms('torrents_edit') || (($UserID == $LoggedUser['ID'] && !$LoggedUser['DisableWiki']) && !($Remastered && !$RemasterYear)));
	
	$FileList = str_replace(array('_','-'), ' ', $FileList);
	$FileList = str_replace('|||','<tr><td>',display_str($FileList));
	$FileList = preg_replace_callback('/\{\{\{([^\{]*)\}\}\}/i','filelist',$FileList);
	$FileList = '<table class="filelist_table" style="overflow-x:auto;"><tr class="colhead_dark"><td><div style="float: left; display: block; font-weight: bold;">File Name'.(check_perms('users_mod') ? ' [<a href="torrents.php?action=regen_filelist&amp;torrentid='.$TorrentID.'">Regenerate</a>]' : '').'</div><div style="float:right; display:block;">'.(empty($FilePath) ? '' : '/'.$FilePath.'/' ).'</div></td><td><strong>Size</strong></td></tr><tr><td>'.$FileList."</table>";

	$ExtraInfo=''; // String that contains information on the torrent (e.g. format and encoding)
	$AddExtra=''; // Separator between torrent properties

	$TorrentUploader = $Username; // Save this for "Uploaded by:" below

	// similar to Torrents::torrent_info()
	if($Format) { $ExtraInfo.=display_str($Format); $AddExtra=' / '; }
	if($Encoding) { $ExtraInfo.=$AddExtra.display_str($Encoding); $AddExtra=' / '; }
	if($HasLog) { $ExtraInfo.=$AddExtra.'Log'; $AddExtra=' / '; }
	if($HasLog && $LogInDB) { $ExtraInfo.=' ('.(int) $LogScore.'%)'; }
	if($HasCue) { $ExtraInfo.=$AddExtra.'Cue'; $AddExtra=' / '; }
	if($Scene) { $ExtraInfo.=$AddExtra.'Scene'; $AddExtra=' / '; }
	if(!$ExtraInfo) {
		$ExtraInfo = $GroupName ; $AddExtra=' / ';
	}
	if($IsSnatched) { $ExtraInfo.=$AddExtra.'<strong class="snatched_torrent_label">Snatched!</strong>'; $AddExtra=' / '; }
	if($FreeTorrent == '1') { $ExtraInfo.=$AddExtra.'<strong class="freeleech_torrent_label">Freeleech!</strong>'; $AddExtra=' / '; }
	if($FreeTorrent == '2') { $ExtraInfo.=$AddExtra.'<strong class="neutral_leech_torrent_label">Neutral Leech!</strong>'; $AddExtra=' / '; }
	if($PersonalFL) { $ExtraInfo.=$AddExtra.'<strong class="personal_freeleech_torrent_label">Personal Freeleech!</strong>'; $AddExtra=' / '; }
	if($Reported) { $ExtraInfo.=$AddExtra.'<strong>Reported</strong>'; $AddExtra=' / '; }
	if(!empty($BadTags)) { $ExtraInfo.=$AddExtra.'<strong>Bad Tags</strong>'; $AddExtra=' / '; }
	if(!empty($BadFolders)) { $ExtraInfo.=$AddExtra.'<strong>Bad Folders</strong>'; $AddExtra=' / '; }
	if(!empty($CassetteApproved)) { $ExtraInfo.=$AddExtra.'<strong>Cassette Approved</strong>'; $AddExtra=' / '; }
	if(!empty($LossymasterApproved)) { $ExtraInfo.=$AddExtra.'<strong>Lossy Master Approved</strong>'; $AddExtra=' / '; }
	if(!empty($LossywebApproved)) { $ExtraInfo.=$AddExtra.'<strong>Lossy WEB Approved</strong>'; $AddExtra = ' / '; }
	if(!empty($BadFiles)) { $ExtraInfo.=$AddExtra.'<strong>Bad File Names</strong>'; $AddExtra=' / '; }
	
	if($GroupCategoryID == 1 
		&& ($RemasterTitle != $LastRemasterTitle
		|| $RemasterYear != $LastRemasterYear
		|| $RemasterRecordLabel != $LastRemasterRecordLabel 
		|| $RemasterCatalogueNumber != $LastRemasterCatalogueNumber
		|| $FirstUnknown
		|| $Media != $LastMedia)) {
		
		$EditionID++;

		if($Remastered && $RemasterYear != 0){
		
			$RemasterName = $RemasterYear;
			$AddExtra = " - ";
			if($RemasterRecordLabel) { $RemasterName .= $AddExtra.display_str($RemasterRecordLabel); $AddExtra=' / '; }
			if($RemasterCatalogueNumber) { $RemasterName .= $AddExtra.display_str($RemasterCatalogueNumber); $AddExtra=' / '; }
			if($RemasterTitle) { $RemasterName .= $AddExtra.display_str($RemasterTitle); $AddExtra=' / '; }			
			$RemasterName .= $AddExtra.display_str($Media);
?>
			<tr class="releases_<?=$ReleaseType?> groupid_<?=$GroupID?> edition group_torrent">
				<td colspan="5" class="edition_info"><strong><a href="#" onclick="toggle_edition(<?=$GroupID?>, <?=$EditionID?>, this, event)" title="Collapse this edition. Hold &quot;Ctrl&quot; while clicking to collapse all editions in this torrent group.">&minus;</a> <?=$RemasterName?></strong></td>
			</tr>
<?
		} else {
			$AddExtra = " / ";
			if(!$Remastered) {
				$MasterName = "Original Release";
				if($GroupRecordLabel) { $MasterName .= $AddExtra.$GroupRecordLabel; $AddExtra=' / '; }
				if($GroupCatalogueNumber) { $MasterName .= $AddExtra.$GroupCatalogueNumber; $AddExtra=' / '; }
			} else {
				$MasterName = "Unknown Release(s)";
			}
			$MasterName .= $AddExtra.display_str($Media);
?>
		<tr class="releases_<?=$ReleaseType?> groupid_<?=$GroupID?> edition group_torrent">
			<td colspan="5" class="edition_info"><strong><a href="#" onclick="toggle_edition(<?=$GroupID?>, <?=$EditionID?>, this, event)" title="Collapse this edition. Hold &quot;Ctrl&quot; while clicking to collapse all editions in this torrent group.">&minus;</a> <?=$MasterName?></strong></td>
		</tr>
<?
		}
	}
	$LastRemasterTitle = $RemasterTitle;
	$LastRemasterYear = $RemasterYear;
	$LastRemasterRecordLabel = $RemasterRecordLabel;
	$LastRemasterCatalogueNumber = $RemasterCatalogueNumber;
	$LastMedia = $Media;
?>

			<tr class="torrent_row releases_<?=$ReleaseType?> groupid_<?=$GroupID?> edition_<?=$EditionID?> group_torrent<?=$IsSnatched ? ' snatched_torrent' : ''?>" style="font-weight: normal;" id="torrent<?=$TorrentID?>">
				<td>
					<span>[ <a href="torrents.php?action=download&amp;id=<?=$TorrentID ?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" title="Download"><?=$HasFile ? 'DL' : 'Missing'?></a>
<?	if (Torrents::can_use_token($Torrent)) { ?>
						| <a href="torrents.php?action=download&amp;id=<?=$TorrentID ?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>&amp;usetoken=1" title="Use a FL Token" onclick="return confirm('Are you sure you want to use a freeleech token here?');">FL</a>
<?	} ?>					
						| <a href="reportsv2.php?action=report&amp;id=<?=$TorrentID?>" title="Report">RP</a>
<?	if($CanEdit) { ?>
						| <a href="torrents.php?action=edit&amp;id=<?=$TorrentID ?>" title="Edit">ED</a>
<?	} ?>
<?	if(check_perms('torrents_delete') || $UserID == $LoggedUser['ID']) { ?>
						| <a href="torrents.php?action=delete&amp;torrentid=<?=$TorrentID ?>" title="Remove">RM</a>
<?	} ?>

						| <a href="torrents.php?torrentid=<?=$TorrentID ?>" title="Permalink">PL</a>
					]</span>
					&raquo; <a href="#" onclick="$('#torrent_<?=$TorrentID?>').toggle(); return false;"><?=$ExtraInfo; ?></a>
				</td>
				<td class="nobr"><?=Format::get_size($Size)?></td>
				<td><?=number_format($Snatched)?></td>
				<td><?=number_format($Seeders)?></td>
				<td><?=number_format($Leechers)?></td>
			</tr>
			<tr class="releases_<?=$ReleaseType?> groupid_<?=$GroupID?> edition_<?=$EditionID?> torrentdetails pad <? if(!isset($_GET['torrentid']) || $_GET['torrentid']!=$TorrentID) { ?>hidden<? } ?>" id="torrent_<?=$TorrentID; ?>">
				<td colspan="5">
					<blockquote>
						Uploaded by <?=Users::format_username($UserID, false, false, false)?> <?=time_diff($TorrentTime);?>
<? if($Seeders == 0){ ?>
						<?
						if ($LastActive != '0000-00-00 00:00:00' && time() - strtotime($LastActive) >= 1209600) { ?>
							<br /><strong>Last active: <?=time_diff($LastActive);?></strong>
						<?} else { ?>
						<br />Last active: <?=time_diff($LastActive);?>
						<?} ?>
						<?
						if ($LastActive != '0000-00-00 00:00:00' && time() - strtotime($LastActive) >= 345678 && time()-strtotime($LastReseedRequest)>=864000) { ?>
						<br /><a href="torrents.php?action=reseed&amp;torrentid=<?=$TorrentID?>&amp;groupid=<?=$GroupID?>">[Request re-seed]</a>
						<?} ?>
						
<? } ?>

					</blockquote>
<? if(check_perms('site_moderate_requests')) { ?>
					<div class="linkbox">
						<a href="torrents.php?action=masspm&amp;id=<?=$GroupID?>&amp;torrentid=<?=$TorrentID?>">[Mass PM snatchers]</a>
					</div>
<? } ?>
					<div class="linkbox">
						<a href="#" onclick="show_peers('<?=$TorrentID?>', 0);return false;">(View peer list)</a>
<? if(check_perms('site_view_torrent_snatchlist')) { ?> 
						<a href="#" onclick="show_downloads('<?=$TorrentID?>', 0);return false;" title="View the list of users that have clicked the &quot;DL&quot; button.">(View download list)</a>
						<a href="#" onclick="show_snatches('<?=$TorrentID?>', 0);return false;" title="View the list of users that have reported a snatch to the tracker.">(View snatch list)</a>
<? } ?>
						<a href="#" onclick="show_files('<?=$TorrentID?>');return false;">(View file list)</a>
<? if($Reported) { ?> 
						<a href="#" onclick="show_reported('<?=$TorrentID?>');return false;">(View report information)</a>
<? } ?>
					</div>
					<div id="peers_<?=$TorrentID?>" class="hidden"></div>
					<div id="downloads_<?=$TorrentID?>" class="hidden"></div>
					<div id="snatches_<?=$TorrentID?>" class="hidden"></div>
					<div id="files_<?=$TorrentID?>" class="hidden"><?=$FileList?></div>
					<div id="spectrals_<?=$TorrentID?>" class="hidden"></div>
<?  if($Reported) { ?> 
					<div id="reported_<?=$TorrentID?>" class="hidden"><?=$ReportInfo?></div>
<? } ?>
					<? if(!empty($Description)) {
						echo '<blockquote>'.$Text->full_format($Description).'</blockquote>';}
					?>
				</td>
			</tr>
<? } ?>
		</table>
<?
$Requests = get_group_requests($GroupID);
if (count($Requests) > 0) {
	$i = 0;
?>
		<div class="box">
			<div class="head"><span style="font-weight: bold;">Requests (<?=count($Requests)?>)</span> <a href="#" style="float:right;" onclick="$('#requests').toggle(); this.innerHTML=(this.innerHTML=='(Hide)'?'(Show)':'(Hide)'); return false;">(Show)</a></div>
			<table id="requests" class="request_table hidden">
				<tr class="colhead">
					<td>Format / Bitrate / Media</td>
					<td>Votes</td>
					<td>Bounty</td>
				</tr>
<?	foreach($Requests as $Request) {
		$RequestVotes = get_votes_array($Request['ID']);

		if($Request['BitrateList'] != "") {
			$BitrateString = implode(", ", explode("|", $Request['BitrateList']));
			$FormatString = implode(", ", explode("|", $Request['FormatList']));
			$MediaString = implode(", ", explode("|", $Request['MediaList']));
			if ($Request['LogCue']) {
				$FormatString .= ' - '.$Request['LogCue'];
			}
		} else {
			$BitrateString = "Unknown";
			$FormatString = "Unknown";
			$MediaString = "Unknown";
		}
?>
				<tr class="requestrows <?=(++$i%2?'rowa':'rowb')?>">
					<td><a href="requests.php?action=view&amp;id=<?=$Request['ID']?>"><?=$FormatString?> / <?=$BitrateString?> / <?=$MediaString?></a></td>
					<td>
						<form class="add_form" name="bounty" id="form_<?=$Request['ID']?>">
							<span id="vote_count_<?=$Request['ID']?>"><?=count($RequestVotes['Voters'])?></span>
							<input type="hidden" id="requestid_<?=$Request['ID']?>" name="requestid" value="<?=$Request['ID']?>" />
							<input type="hidden" id="auth" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
							&nbsp;&nbsp; <a href="javascript:Vote(0, <?=$Request['ID']?>)"><strong>(+)</strong></a>
						</form>
					</td>
					<td><?=Format::get_size($RequestVotes['TotalBounty'])?></td>
				</tr>
<?	} ?>
			</table>
		</div>
<?
}
$Collages = $Cache->get_value('torrent_collages_'.$GroupID);
if(!is_array($Collages)) {
	$DB->query("SELECT c.Name, c.NumTorrents, c.ID FROM collages AS c JOIN collages_torrents AS ct ON ct.CollageID=c.ID WHERE ct.GroupID='$GroupID' AND Deleted='0' AND CategoryID!='0'");
	$Collages = $DB->to_array();
	$Cache->cache_value('torrent_collages_'.$GroupID, $Collages, 3600*6);
}
if(count($Collages)>0) {
	if (count($Collages) > MAX_COLLAGES) {
		// Pick some at random
		$Range = range(0,count($Collages) - 1);
		shuffle($Range);
		$Indices = array_slice($Range, 0, MAX_COLLAGES);
		$SeeAll = ' <a href="#" onclick="$(\'.collage_rows\').toggle(); return false;">(See all)</a>';
	} else {
		$Indices = range(0, count($Collages)-1);
		$SeeAll = '';
	}
?>
		<table class="collage_table" id="collages">
			<tr class="colhead">
				<td width="85%"><a href="#">&uarr;</a>&nbsp;This album is in <?=count($Collages)?> collage<?=((count($Collages)>1)?'s':'')?><?=$SeeAll?></td>
				<td># torrents</td>
			</tr>
<?	foreach ($Indices as $i) { 
		list($CollageName, $CollageTorrents, $CollageID) = $Collages[$i];
		unset($Collages[$i]);
?>
			<tr>
				<td><a href="collages.php?id=<?=$CollageID?>"><?=$CollageName?></a></td>
				<td><?=$CollageTorrents?></td>
			</tr>
<?	}
	foreach ($Collages as $Collage) { 
		list($CollageName, $CollageTorrents, $CollageID) = $Collage;
?>
			<tr class="collage_rows hidden">
				<td><a href="collages.php?id=<?=$CollageID?>"><?=$CollageName?></a></td>
				<td><?=$CollageTorrents?></td>
			</tr>
<?	} ?>
		</table>
<?
}

$PersonalCollages = $Cache->get_value('torrent_collages_personal_'.$GroupID);
if(!is_array($PersonalCollages)) {
	$DB->query("SELECT c.Name, c.NumTorrents, c.ID FROM collages AS c JOIN collages_torrents AS ct ON ct.CollageID=c.ID WHERE ct.GroupID='$GroupID' AND Deleted='0' AND CategoryID='0'");
	$PersonalCollages = $DB->to_array(false, MYSQLI_NUM);
	$Cache->cache_value('torrent_collages_personal_'.$GroupID, $PersonalCollages, 3600*6);
}

if(count($PersonalCollages)>0) { 
	if (count($PersonalCollages) > MAX_PERS_COLLAGES) {
		// Pick some at random
		$Range = range(0,count($PersonalCollages) - 1);
		shuffle($Range);
		$Indices = array_slice($Range, 0, MAX_PERS_COLLAGES);
		$SeeAll = ' <a href="#" onclick="$(\'.personal_rows\').toggle(); return false;">(See all)</a>';
	} else {
		$Indices = range(0, count($PersonalCollages)-1);
		$SeeAll = '';
	}
?>
		<table class="collage_table" id="personal_collages">
			<tr class="colhead">
				<td width="85%"><a href="#">&uarr;</a>&nbsp;This album is in <?=count($PersonalCollages)?> personal collage<?=((count($PersonalCollages)>1)?'s':'')?><?=$SeeAll?></td>
				<td># torrents</td>
			</tr>
<?	foreach ($Indices as $i) { 
		list($CollageName, $CollageTorrents, $CollageID) = $PersonalCollages[$i];
		unset($PersonalCollages[$i]);
?>
			<tr>
				<td><a href="collages.php?id=<?=$CollageID?>"><?=$CollageName?></a></td>
				<td><?=$CollageTorrents?></td>
			</tr>
<?	}
	foreach ($PersonalCollages as $Collage) { 
		list($CollageName, $CollageTorrents, $CollageID) = $Collage;
?>
			<tr class="personal_rows hidden">
				<td><a href="collages.php?id=<?=$CollageID?>"><?=$CollageName?></a></td>
				<td><?=$CollageTorrents?></td>
			</tr>
<?	} ?>
		</table>
<?
}
// Matched Votes
include(SERVER_ROOT.'/sections/torrents/voter_picks.php');
?>
		<div class="box">
			<div class="head"><a href="#">&uarr;</a>&nbsp;<strong><?=(!empty($ReleaseType) ? $ReleaseTypes[$ReleaseType].' info' : 'Info' )?></strong></div>
			<div class="body"><? if ($WikiBody!="") { echo $WikiBody; } else { echo "There is no information on this torrent."; } ?></div>
		</div>
<?
// --- Comments ---

// gets the amount of comments for this group
$Results = $Cache->get_value('torrent_comments_'.$GroupID);
if($Results === false) {
	$DB->query("SELECT
			COUNT(c.ID)
			FROM torrents_comments as c
			WHERE c.GroupID = '$GroupID'");
	list($Results) = $DB->next_record();
	$Cache->cache_value('torrent_comments_'.$GroupID, $Results, 0);
}

if(isset($_GET['postid']) && is_number($_GET['postid']) && $Results > TORRENT_COMMENTS_PER_PAGE) {
	$DB->query("SELECT COUNT(ID) FROM torrents_comments WHERE GroupID = $GroupID AND ID <= $_GET[postid]");
	list($PostNum) = $DB->next_record();
	list($Page,$Limit) = Format::page_limit(TORRENT_COMMENTS_PER_PAGE,$PostNum);
} else {
	list($Page,$Limit) = Format::page_limit(TORRENT_COMMENTS_PER_PAGE,$Results);
}

//Get the cache catalogue
$CatalogueID = floor((TORRENT_COMMENTS_PER_PAGE*$Page-TORRENT_COMMENTS_PER_PAGE)/THREAD_CATALOGUE);
$CatalogueLimit=$CatalogueID*THREAD_CATALOGUE . ', ' . THREAD_CATALOGUE;

//---------- Get some data to start processing

// Cache catalogue from which the page is selected, allows block caches and future ability to specify posts per page
$Catalogue = $Cache->get_value('torrent_comments_'.$GroupID.'_catalogue_'.$CatalogueID);
if($Catalogue === false) {
	$DB->query("SELECT
			c.ID,
			c.AuthorID,
			c.AddedTime,
			c.Body,
			c.EditedUserID,
			c.EditedTime,
			u.Username
			FROM torrents_comments as c
			LEFT JOIN users_main AS u ON u.ID=c.EditedUserID
			WHERE c.GroupID = '$GroupID'
			ORDER BY c.ID
			LIMIT $CatalogueLimit");
	$Catalogue = $DB->to_array(false,MYSQLI_ASSOC);
	$Cache->cache_value('torrent_comments_'.$GroupID.'_catalogue_'.$CatalogueID, $Catalogue, 0);
}

//This is a hybrid to reduce the catalogue down to the page elements: We use the page limit % catalogue
$Thread = array_slice($Catalogue,((TORRENT_COMMENTS_PER_PAGE*$Page-TORRENT_COMMENTS_PER_PAGE)%THREAD_CATALOGUE),TORRENT_COMMENTS_PER_PAGE,true);
?>
	<div class="linkbox"><a name="comments"></a>
<?
$Pages=Format::get_pages($Page,$Results,TORRENT_COMMENTS_PER_PAGE,9,'#comments');
echo $Pages;
?>
	</div>
<?

//---------- Begin printing
foreach($Thread as $Key => $Post) {
	list($PostID, $AuthorID, $AddedTime, $Body, $EditedUserID, $EditedTime, $EditedUsername) = array_values($Post);
	list($AuthorID, $Username, $PermissionID, $Paranoia, $Artist, $Donor, $Warned, $Avatar, $Enabled, $UserTitle) = array_values(Users::user_info($AuthorID));
?>
<table class="forum_post box vertical_margin<?=!Users::has_avatars_enabled() ? ' noavatar' : ''?>" id="post<?=$PostID?>">
	<colgroup>
<?	if (Users::has_avatars_enabled()) { ?>
		<col class="col_avatar" />
<? 	} ?>
		<col class="col_post_body" />
	</colgroup>
	<tr class="colhead_dark">
		<td colspan="<?=Users::has_avatars_enabled() ? 2 : 1?>">
			<div style="float:left;"><a class="post_id" href="torrents.php?id=<?=$GroupID?>&amp;postid=<?=$PostID?>#post<?=$PostID?>">#<?=$PostID?></a>
				<strong><?=Users::format_username($AuthorID, true, true, true, true)?></strong> <?=time_diff($AddedTime)?>
				- <a href="#quickpost" onclick="Quote('<?=$PostID?>','<?=$Username?>');">[Quote]</a>
<? 	if ($AuthorID == $LoggedUser['ID'] || check_perms('site_moderate_forums')) { ?>
				- <a href="#post<?=$PostID?>" onclick="Edit_Form('<?=$PostID?>','<?=$Key?>');">[Edit]</a>
<? 	}
	if (check_perms('site_moderate_forums')) { ?>
				- <a href="#post<?=$PostID?>" onclick="Delete('<?=$PostID?>');">[Delete]</a>
<?	} ?>
			</div>
			<div id="bar<?=$PostID?>" style="float:right;">
				<a href="reports.php?action=report&amp;type=torrents_comment&amp;id=<?=$PostID?>">[Report]</a>
<?	if (check_perms('users_warn') && $AuthorID != $LoggedUser['ID']) {
		$AuthorInfo = Users::user_info($AuthorID);
		if ($LoggedUser['Class'] >= $AuthorInfo['Class']) {
?>
				<form class="manage_form hidden" name="user" id="warn<?=$PostID?>" action="" method="post">
					<input type="hidden" name="action" value="warn" />
					<input type="hidden" name="groupid" value="<?=$GroupID?>" />
					<input type="hidden" name="postid" value="<?=$PostID?>" />
					<input type="hidden" name="userid" value="<?=$AuthorID?>" />
					<input type="hidden" name="key" value="<?=$Key?>" />
				</form>
				- <a href="#" onclick="$('#warn<?=$PostID?>').raw().submit(); return false;">[Warn]</a>
<?		}
	}
?>
				&nbsp;
				<a href="#">&uarr;</a>
			</div>
		</td>
	</tr>
	<tr>
<?	if (Users::has_avatars_enabled()) { ?>
		<td class="avatar" valign="top">
		<?=Users::show_avatar($Avatar, $Username, $HeavyInfo['DisableAvatars'])?>
		</td>
<?	} ?>
		<td class="body" valign="top">
			<div id="content<?=$PostID?>">
<?=$Text->full_format($Body)?>
<?	if ($EditedUserID) { ?>
				<br />
				<br />
<?		if (check_perms('site_admin_forums')) { ?>
				<a href="#content<?=$PostID?>" onclick="LoadEdit('torrents', <?=$PostID?>, 1); return false;">&laquo;</a> 
<? 		} ?>
				Last edited by
				<?=Users::format_username($EditedUserID, false, false, false) ?> <?=time_diff($EditedTime,2,true,true)?>
<?	} ?>
			</div>
		</td>
	</tr>
</table>
<? } ?>
		<div class="linkbox">
		<?=$Pages?>
		</div>
<? if (!$LoggedUser['DisablePosting']) { ?>
			<br />
			<div id="reply_box">
				<h3>Post reply</h3>
				<div class="box pad">
					<table id="quickreplypreview" class="forum_post box vertical_margin hidden" style="text-align:left;">
						<colgroup>
<?	if(Users::has_avatars_enabled()) { ?>
							<col class="col_avatar" />
<? 	} ?>
							<col class="col_post_body" />
						</colgroup>
						<tr class="colhead_dark">
							<td colspan="<?=Users::has_avatars_enabled() ? 2 : 1?>">
								<span style="float:left;"><a href='#quickreplypreview'>#XXXXXX</a>
								by <strong><?=Users::format_username($LoggedUser['ID'], true, true, true, true)?></strong> Just now
								<a href="#quickreplypreview">[Report Comment]</a>
								</span>
								<span id="barpreview" style="float:right;">
									<a href="#">&uarr;</a>
								</span>
							</td>
						</tr>
						<tr>
					<?	if (Users::has_avatars_enabled()) { ?>
							<td class="avatar" valign="top">
							<?=Users::show_avatar($LoggedUser['Avatar'], $LoggedUser['Username'], $HeavyInfo['DisableAvatars'])?>
							</td>
					<?	} ?>
							<td class="body" valign="top">
								<div id="contentpreview" style="text-align:left;"></div>
							</td>
						</tr>
					</table>
					<form class="send_form center" name="reply" id="quickpostform" action="" onsubmit="quickpostform.submit_button.disabled=true;" method="post">
						<div id="quickreplytext">
							<input type="hidden" name="action" value="reply" />
							<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
							<input type="hidden" name="groupid" value="<?=$GroupID?>" />
							<textarea id="quickpost" name="body"  cols="70"  rows="8"></textarea> <br />
						</div>
						<input id="post_preview" type="button" value="Preview" onclick="if(this.preview){Quick_Edit();}else{Quick_Preview();}" />
						<input type="submit" id="submit_button" value="Post reply" />
					</form>
				</div>
			</div>
<? } ?>
	</div>
</div>
<? View::show_footer(); ?>
