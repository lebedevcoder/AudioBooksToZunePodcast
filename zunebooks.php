<?php
/*
 * This php file returns an rss feed of audiobooks in the format that
 * Zune expects a podcast rss feed to be in. 
 * 
 * A webserver is required in order to use this file. I recommend xampp 
 * for those with little to no experience in setting up a webserver: 
 * http://www.apachefriends.org
 * 
 * The php file will accept an optional GET variable called "subdir" 
 * from the url. If the "subdir" variable is missing, the program 
 * treats the current working directory (cwd) as the directory to find 
 * all target files (should be the dir that this file is in). 
 * The "subdir" variable should contain the subdirectory(ies) within the cwd
 * that contain the target files, with each subdirectory separated by an exclamation point (!).
 * The subdirectories should be ordered from the subdirectory closest to the cwd to 
 * the one farthest (zunebook.php?subdir=firstsubdirectory!secondsubdirectory).
 * The target directory must be the cwd, or contained within a subfolder of
 * the cwd (or a subfolder of a subfolder, etc... etc...). There is no provision for
 * having the target directory be outside the cwd.  
 *  
 * The program makes the following assumptions:
 * The name of the relvant directory should be used as the title of the book/podcast, unless there is a title.txt file containing the title
 * There is a description.txt file in the target directory containing the description of the book.
 *    If this description.txt does not exist, the program looks for any file with a .txt or .nfo
 *    extension (other than author.txt) and uses that as the description. If no .txt or .nfo file exists then 
 *    it is assumed that the description should be blank. If more than one .nfo or .txt file exists, then
 * 	  the program uses the last one alphabetically.
 * There is an author.txt file in the target directory containing the name of the author.
 *    If author.txt does not exist, the first two words of the directory are used as the author's name.
 * The image for the book is contained in the last .jpg/.gif file to be found in the target directory.
 * If there are no .jpg/.gif files in the target directory, there will be no image associated with the book
 * The audio files for the audiobook are in mp3 or m4a format and are in alphabetical order from the first file to play to the last
 * The first file to play will be given the most recent pubDate (midnight 12/31/2008), 
 *    and each subsequent file will be given a pubDate one day less (12/30/2008, 12/29/2008, etc)
 * 
 * Example:
 * Let's say this php file is within C:\audiobooks\  
 * the audiobook you want to add is in: C:\audiobooks\JK Rowling\JK Rowling - Harry Potter and the Sorcerer's Stone\ 
 * Note that spaces are ok within folder and file names, as well as apostrophes.
 *  
 * Depending on how you configure your webserver's virtual directories and alias', the url of the podcast will be something like:
 * http://localhost/zunebook.php?subdir=JK Rowling!JK Rowling - Harry Potter and the Sorcerer's Stone
 * http://localhost/audiobooks/zunebook.php?subdir=JK Rowling!JK Rowling - Harry Potter and the Sorcerer's Stone
 * 
 * There are two reasons to put the author's name within the folder containing your podcasts. 
 * The first is that the program will parse the first two words in that directory as the author's name (if there isn't an author.txt file).
 * The second reason is that this will cause your zune to list your audiobooks alphabetically by author.
 * 
 * Let's say you wanted to have a book series in your zune, with each book in the series
 * listed as a separate podcast, and in the correct order. Your file structure would look
 * something like this:
 * C:\audiobooks\JK Rowling\JK Rowling - Harry Potter Series 01 - Sorcerer's Stone
 * C:\audiobooks\JK Rowling\JK Rowling - Harry Potter Series 02 - Chamber of Secrets
 * C:\audiobooks\JK Rowling\JK Rowling - Harry Potter Series 03 - Prisoner of Azkaban
 * C:\audiobooks\JK Rowling\JK Rowling - Harry Potter Series 04 - Goblet of Fire
 * C:\audiobooks\JK Rowling\JK Rowling - Harry Potter Series 05 - Order of the Phoenix
 * C:\audiobooks\JK Rowling\JK Rowling - Harry Potter Series 06 - Half-Blood Prince
 * C:\audiobooks\JK Rowling\JK Rowling - Harry Potter Series 07 - Deathly Hallows 
 * 
 * Your podcast URLs would look something like this:
 * http://localhost/audiobooks/zunebook.php?subdir=JK Rowling!JK Rowling - Harry Potter Series 01 - Sorcerer's Stone
 * http://localhost/audiobooks/zunebook.php?subdir=JK Rowling!JK Rowling - Harry Potter Series 02 - Chamber of Secrets
 * http://localhost/audiobooks/zunebook.php?subdir=JK Rowling!JK Rowling - Harry Potter Series 03 - Prisoner of Azkaban
 * http://localhost/audiobooks/zunebook.php?subdir=JK Rowling!JK Rowling - Harry Potter Series 04 - Goblet of Fire
 * http://localhost/audiobooks/zunebook.php?subdir=JK Rowling!JK Rowling - Harry Potter Series 05 - Order of the Phoenix
 * http://localhost/audiobooks/zunebook.php?subdir=JK Rowling!JK Rowling - Harry Potter Series 06 - Half-Blood Prince
 * http://localhost/audiobooks/zunebook.php?subdir=JK Rowling!JK Rowling - Harry Potter Series 07 - Deathly Hallows 
*/

//Set timezone to EST
date_default_timezone_set('America/New_York');

// set the date for the most recent podcast to be midnight 12/31/2008
$podcastDateTime = mktime(0, 0, 0, 12, 31, 2008);
	
// assume cwd is the directory we are working with
$bookPath = getcwd();

//Get the url path for cwd (begins with http:// and ends with /)
$urlPath = "http://" . dirname($_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]) . "/";

// see if we were fed sub-directory(ies) in the url
if ($_GET['subdir'] != null) {
	
	$pathArray = explode("!", $_GET['subdir']);

	// add each sub directory to bookPath and url
	foreach($pathArray as $subDir) {
		$bookPath .= "\\" . $subDir;
		$urlPath .= "$subDir/";
	}
}

// make sure we have a legitimate directory to work with
if (file_exists($bookPath . "\\")) {
	
	// Get the title from the title.txt file, 
	// or from the directory name if there is no title.txt
	if (file_exists($bookPath . "\\title.txt")) {
		// put the sanitized contents of the file as the author
		$title = cleanFileText($bookPath . "\\title.txt");
	}
	else {
		$title = basename($bookPath);
	}
	
	$description = '';

	//Get the description of the book from the desciption.txt file if there is one
	// otherwise get the description from the last .txt or .nfo file
	if (file_exists($bookPath . "\\description.txt")) {
		// put the sanitized contents of the file in the description
		$description = cleanFileText($bookPath . "\\description.txt");
	}
	else {
		foreach(glob($bookPath ."\\{*.txt,*.nfo}", GLOB_BRACE) as $textFile) {
			// make sure this isn't the author.txt file
			if (basename($textFile) != 'author.txt') {
				// put the sanitized contents of the file in the description
				$description = cleanFileText($textFile);
			}
		}
	}

	// Get the last jpg image in the directory
	foreach(glob($bookPath ."\\{*.jpg,*.gif}", GLOB_BRACE) as $imageFile) {
		
		// get the url path for the image
		$imageURL = $urlPath . basename($imageFile) ;
		
		// get the characteristics of the image file
		list($imageWidth, $imageHeight, $imageType, $imageAttr) = getimagesize($imageFile);
	}

	// Get the author from the author.txt file, 
	// or the first two words of the directory name if there is no file
	if (file_exists($bookPath . "\\author.txt")) {
		// put the sanitized contents of the file as the author
		$author = cleanFileText($bookPath . "\\author.txt");
	}
	else {
		// need to get the author from the fist two words in the directory name
		
		// place each word in the directory as an item in an array
		$dirNameArray = explode(" ", basename($bookPath));
		
		// shorten the array so that it only has two word items
		array_splice($dirNameArray, 2);
		
		// assign those two word items as the author
		$author = implode(" ", $dirNameArray);
	}

	// output the correct header for an rss feed
	header("Content-Type: application/rss+xml");
	echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	echo '<rss version="2.0">' . "\n";

	// output the details of the channel
	echo '	<channel>'. "\n";
	echo '		<title>' .  $title . '</title>' . "\n";
	echo '		<description>' . $description . '</description>' . "\n";

	// make sure we have an image to output
	if ($imageURL != '') {
		echo '		<image>' . "\n";
		echo '			<url>' . $imageURL . '</url>' . "\n"; 
		echo '			<width>' . $imageWidth . '</width>' . "\n"; 
		echo '			<height>' . $imageHeight . '</height>' . "\n"; 
		echo '		</image>' . "\n";
	}

	// Loop through each mp3/mp4 in CWD and add each one as an item
	foreach(glob($bookPath ."\\{*.mp3,*.m4a}", GLOB_BRACE) as $audioFile) {
		// get the filename (without the .mp3 extension) and encode any characters that are special for html
		$fileName = htmlspecialchars(pathinfo($urlPath . basename($audioFile), PATHINFO_FILENAME ));
		
		// get the url of the audio file, and encode any characters that are special for html
		$audioFileURL = htmlspecialchars($urlPath . basename($audioFile));
		
		// get the size of the mp3 file
		$audioFileSize = filesize($audioFile);
		
		// output the item
		echo '<item>' . "\n";	
		echo '	<title>' . $fileName. '</title>' . "\n";
		echo '	<description>' . $fileName  . '</description>' . "\n";
		echo '	<guid>' . $audioFileURL . '</guid>' . "\n";
		echo '	<enclosure url="' . $audioFileURL . '" length="' . $audioFileSize . '" type="audio/mpeg"/>' . "\n";
		echo '	<author>' . $author . '</author>' . "\n";
		echo '	<category>Podcasts</category>' . "\n";
		echo '	<pubDate>' . date("r", $podcastDateTime) . '</pubDate>' . "\n";
		echo '</item>' . "\n";
		
		// Set the date of the next podcast back by one day
		$podcastDateTime -= 86400;
	}

	// close the channel and rss tags
	echo '	</channel>' . "\n";
	echo '</rss>';
}
else {
	echo "Directory doesn't exist: $bookPath";
}

function cleanFileText ($filePath) {
	$result = '';
	
	$result = filter_var(trim(file_get_contents($filePath)),FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_STRIP_HIGH);
	return $result;
}

?>