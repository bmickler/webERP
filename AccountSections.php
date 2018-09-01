<?php
/**
 * Defines the sections in the general ledger reports
 * 
 * @package webERP
 * @subpackage GL
 * @link http://www.weberp.org webERP Homepage
 * @copyright 2003 - Present webERP.org
 * @license [GNU General Public License version 2.0 (GPLv2)](https://www.gnu.org/licenses/gpl-2.0.html)
 */
/**
 * Set up the environment
 */
include('includes/session.php');
$Title = _('Account Sections');
$ViewTopic = 'GeneralLedger';
$BookMark = 'AccountSections';
include('includes/header.php');

/**
 * First, test to ensure that ate lease Incoe and Cost Of Sales are there
 */
$sql= "SELECT sectionid FROM accountsection WHERE sectionid=1";
$result = DB_query($sql);

if( DB_num_rows($result) == 0 ) {
    $sql = "INSERT INTO accountsection (sectionid,
                            sectionname)
            VALUES (1,
                            'Income')";
        $result = DB_query($sql);
}

$sql= "SELECT sectionid FROM accountsection WHERE sectionid=2";
$result = DB_query($sql);

if( DB_num_rows($result) == 0 ) {
    $sql = "INSERT INTO accountsection (sectionid,
                            sectionname)
            VALUES (2,
                            'Cost Of Sales')";
    $result = DB_query($sql);
}
// DONE WITH MINIMUM TESTS

/**
 *  Reset the $Errors messages
 */
if(isset($Errors)) {
    unset($Errors);
}
$Errors = array();

if(isset($_POST['submit'])) {
    /**
     * The form was submitted by clicking the Submit button, we are attempting to
     * add a new account section.
     */

    /**
     * initialise no input errors assumed initially before we test
     */
    $InputError = 0;
    $i=1;


    /**
     * First off validate that inputs are sensible
     */
    
    /**
     * Is there already an account section with this name?
     */
    if(isset($_POST['SectionID'])) {
        $sql="SELECT sectionid
                FROM accountsection
                WHERE sectionid='".$_POST['SectionID']."'";
        $result=DB_query($sql);

        if((DB_num_rows($result)!=0 AND !isset($_POST['SelectedSectionID']))) {
            $InputError = 1;
            prnMsg( _('The account section already exists in the database'),'error');
            $Errors[$i] = 'SectionID';
            $i++;
        }
    }
    
    /**
     * Sanitize user input
     * 
     * @todo:  Maybe put this check before any other SQL statements to prevent 
     * sql injection or other nefarious actions which can affect even a SELECT statement.
     */
    if(ContainsIllegalCharacters($_POST['SectionName'])) {
        $InputError = 1;
        prnMsg( _('The account section name cannot contain any illegal characters') ,'error');
        $Errors[$i] = 'SectionName';
        $i++;
    }
    /**
     * Make sure string is long enough
     * 
     * @todo:  add some check to the ui/ux to give direction on field requirements so
     * we never fail this check and don't make the user guess as to what the min.
     * field requirements are.
     */
    if(mb_strlen($_POST['SectionName'])==0) {
        $InputError = 1;
        prnMsg( _('The account section name must contain at least one character') ,'error');
        $Errors[$i] = 'SectionName';
        $i++;
    }
    /**
     * Section ID must be integer
     */
    if(isset($_POST['SectionID']) AND (!is_numeric($_POST['SectionID']))) {
        $InputError = 1;
        prnMsg( _('The section number must be an integer'),'error');
        $Errors[$i] = 'SectionID';
        $i++;
    }
    /**
     * SectionID cannot be a decimal/float
     * @todo:  maybe combine this check with the above to simplify code
     */
    if(isset($_POST['SectionID']) AND mb_strpos($_POST['SectionID'],".")>0) {
        $InputError = 1;
        prnMsg( _('The section number must be an integer'),'error');
        $Errors[$i] = 'SectionID';
        $i++;
    }
    
    /**
     * Update the section name
     */
    if(isset($_POST['SelectedSectionID']) AND $_POST['SelectedSectionID']!='' AND $InputError !=1) {
        /**
         * SelectedSectionID could also exist if submit had not been clicked 
         * this code would not run in this case cos submit is false of course 
         * see the delete code below
         */
        $sql = "UPDATE accountsection SET sectionname='" . $_POST['SectionName'] . "'
                    WHERE sectionid = '" . $_POST['SelectedSectionID'] . "'";
        $msg = _('Record Updated');
    } elseif($InputError !=1) {
	/**
         * SelectedSectionID is null cos no item selected on first time round so 
         * must be adding a record must be submitting new entries in the new 
         * account section form
         */
        $sql = "INSERT INTO accountsection (sectionid,
                            sectionname
                    ) VALUES (
                            '" . $_POST['SectionID'] . "',
                            '" . $_POST['SectionName'] ."')";
        $msg = _('Record inserted');
	}

	if($InputError!=1) {
            /**
             * run the SQL from either of the above possibilites
             */
            $result = DB_query($sql);
            prnMsg($msg,'success');
            unset ($_POST['SelectedSectionID']);
            unset ($_POST['SectionID']);
            unset ($_POST['SectionName']);
	}
} elseif(isset($_GET['delete'])) {
    /**
     * The form was not submitted, rather, the page was loaded with the $_GET 
     * argument to DELETE an account section
     */
    
    /**
     * Prevent deletion of this account section if there are accounts associated with
     * this account section in the 'accountgroups' table.
     */
    $sql= "SELECT COUNT(sectioninaccounts) AS sections FROM accountgroups WHERE sectioninaccounts='" 
            . $_GET['SelectedSectionID'] . "'";
    $result = DB_query($sql);
    $myrow = DB_fetch_array($result);
    if($myrow['sections']>0) {
        prnMsg( _('Cannot delete this account section because general ledger accounts groups have been created using this section'),'warn');
        echo '<div>',
                '<br />', _('There are'), ' ', $myrow['sections'], ' ', _('general ledger accounts groups that refer to this account section'),
                '</div>';
    } else {
        /**
         * No children accounts detected, delete the Account Section
         */
        $sql = "SELECT sectionname FROM accountsection WHERE sectionid='".$_GET['SelectedSectionID'] . "'";
        $result = DB_query($sql);
        $myrow = DB_fetch_array($result);
        $SectionName = $myrow['sectionname'];

        $sql="DELETE FROM accountsection WHERE sectionid='" . $_GET['SelectedSectionID'] . "'";
        $result = DB_query($sql);
        prnMsg( $SectionName . ' ' . _('section has been deleted') . '!','success');
    }/**end if account group used in GL accounts */
    
    /**
     * Reset URL and FORM variables to prevent additional edits during this page visit.
     */
    unset ($_GET['SelectedSectionID']);
    unset ($_GET['delete']);
    unset ($_POST['SelectedSectionID']);
    unset ($_POST['SectionID']);
    unset ($_POST['SectionName']);
}

/**
 * Display listing of existing account sections.
 */
if(!isset($_GET['SelectedSectionID']) AND !isset($_POST['SelectedSectionID'])) {
    /**
     * An account section could be posted when one has been edited and is being 
     * updated or GOT when selected for modification. SelectedSectionID will exist 
     * because it was sent with the page in a GET. If its the first time the page 
     * has been displayed with no parameters then none of the above are true and 
     * the list of account groups will be displayed with links to delete or edit 
     * each. These will call the same page again and allow update/input or 
     * deletion of the records
     */

    $sql = "SELECT sectionid,
                    sectionname
            FROM accountsection
            ORDER BY sectionid";

    $ErrMsg = _('Could not get account group sections because');
    $result = DB_query($sql,$ErrMsg);

    echo '<p class="page_title_text"><img alt="" class="noprint" src="', $RootPath, '/css/', $Theme,
            '/images/maintenance.png" title="', // Icon image.
            _('Account Sections'), '" /> ', // Icon title.
            _('Account Sections'), '</p>';// Page title.

    echo '<br />
    <table class="selection">
            <thead>
                    <tr>
                            <th class="ascending">', _('Section Number'), '</th>
                            <th class="ascending">', _('Section Description'), '</th>
                            <th class="noprint" colspan="2">&nbsp;</th>
                    </tr>
            </thead>
            <tbody>';

    while ($myrow = DB_fetch_array($result)) {
        /**
         * List all existing account sections along with available management 
         * options
         */
        echo '<tr class="striped_row">
                <td class="number">', $myrow['sectionid'], '</td>
                <td class="text">', $myrow['sectionname'], '</td>
                <td class="noprint"><a href="', htmlspecialchars($_SERVER['PHP_SELF'].'?SelectedSectionID='.urlencode($myrow['sectionid']), ENT_QUOTES, 'UTF-8'), '">', _('Edit'), '</a></td>
                <td class="noprint">';
        
        if( $myrow['sectionid'] == '1' or $myrow['sectionid'] == '2' ) {
            /**
             * Prevent deletion of critically necessary account sections
             */
            echo '<b>', _('Restricted'), '</b>';
        } else {
            echo '<a href="', htmlspecialchars($_SERVER['PHP_SELF'].'?SelectedSectionID='.urlencode($myrow['sectionid']).'&delete=1', ENT_QUOTES, 'UTF-8'), '">', _('Delete'), '</a>';
        }
        echo '</td>
                </tr>';
    } //END WHILE LIST LOOP
    echo '</tbody>
            </table>';
} //end of ifs and buts!

/**
 * We are editing a selected section ID
 */
/**
 * Display the section's current details
 */
if(isset($_POST['SelectedSectionID']) or isset($_GET['SelectedSectionID'])) {
	echo '<div class="centre"><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">' . _('Review Account Sections') . '</a></div>';
}

/**
 * Display the edit form 
 */
if(!isset($_GET['delete'])) {

    echo '<form action="', htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'), '" id="AccountSections" method="post">',
            '<div class="noprint"><br />',
            '<input name="FormID" type="hidden" value="', $_SESSION['FormID'], '" />';

    if(isset($_GET['SelectedSectionID'])) {

        $sql = "SELECT sectionid,
                        sectionname
                FROM accountsection
                WHERE sectionid='" . $_GET['SelectedSectionID'] ."'";

        $result = DB_query($sql);
        if( DB_num_rows($result) == 0 ) {
            prnMsg( _('Could not retrieve the requested section please try again.'),'warn');
            unset($_GET['SelectedSectionID']);
        } else {
            $myrow = DB_fetch_array($result);

            $_POST['SectionID'] = $myrow['sectionid'];
            $_POST['SectionName'] = $myrow['sectionname'];

            echo '<input name="SelectedSectionID" type="hidden" value="', $_POST['SectionID'], '" />
                    <table class="selection">
                    <thead>
                        <tr>
                            <th colspan="2">', _('Edit Account Section Details'), '</th>
                        </tr>
                    </thead>
                    <tbody>
                            <tr>
                                    <td>', _('Section Number'), ':</td>
                                    <td>', $_POST['SectionID'], '</td>
                            </tr>';
            }
	} else {
            /**
             * First time through, show new account addition form.
             */
            if(!isset($_POST['SelectedSectionID'])) {
                $_POST['SelectedSectionID']='';
            }
            if(!isset($_POST['SectionID'])) {
                $_POST['SectionID']='';
            }
            if(!isset($_POST['SectionName'])) {
                $_POST['SectionName']='';
            }
            echo '<table class="selection">
                    <thead>
                        <tr>
                            <th colspan="2">', _('New Account Section Details'), '</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>', _('Section Number'), ':</td>
                            <td><input autofocus="autofocus" ',
                                ( in_array('SectionID',$Errors) ? 'class="inputerror number"' : 'class="number" ' ),
                                'maxlength="4" name="SectionID" required="required" size="4" tabindex="1" type="text" value="', $_POST['SectionID'], '" /></td>
                        </tr>';
	}
	echo	'<tr>
                        <td>', _('Section Description'), ':</td>
                        <td><input ',
                            ( in_array('SectionName',$Errors) ? 'class="inputerror text" ' : 'class="text" ' ),
                            'maxlength="30" name="SectionName" required="required" size="30" tabindex="2" type="text" value="', $_POST['SectionName'], '" /></td>
                    </tr>
                    <tr>
                            <td class="centre" colspan="2"><input name="submit" tabindex="3" type="submit" value="', _('Enter Information'), '" /></td>
                    </tr>
		</tbody>
		</table>
		<br />
		</div>
		</form>';
} //end if record deleted no point displaying form to add record

include('includes/footer.php');
?>