# Podcast RSS Editor
Started a [design podcast](https://anyway.fm/) couple of years ago, and in the very beginning, I manually updated the RSS feed for every episode and very soon, I got sick of the 'tag dancing', so tried a little bit building this tool, a very simple Podcast RSS editor in PHP for myself.

![](https://raw.githubusercontent.com/JJYing/Podcast-RSS-Editor/master/assets/screenshot.jpg)

# 01. Features
- Add / Remove / Duplicate episodes from existing RSS feeds.
- Analyzing publish date and episode duration information.

# 02. Usage
- Put the XML file you wanna edit in the root and select it in the '**Settings**' panel and then you can edit / add episodes.

# 03. Authentication
 - There's a very simple authentication done with `.htaccess`, so all you gotta do is to generate the `.htpasswd` file content [in this site](http://www.htaccesstools.com/htpasswd-generator/) and replace it.
 - The parameter **AuthUserFile** of the `.htaccess` file is the path to the `.htpasswd` that **must to be the real path** at all, if needed, change it.
 - By default authentication is: user `admin` and password `admin`

# 04. Change Log
- [Feb 3, 2019] Updated the layout compatibility.
- [Jul 27, 2017] Added ‘**Preview**’ function for episode link, image file and audio file.
- [Dec 13, 2016] Added ‘**duplicate**’ and ‘**delete**’ functions.
- [Jul 20, 2016] Added multi-language support: Simplified Chinese, Traditional Chinese, English.

# 05. Alternatives
- [Itunes podcast XML generator](http://codepen.io/jon-walstedt/pen/jsIup) by Jon Wålstedt
- [Podcast Generator](http://www.podcastgenerator.net/)
- Any Podcast hosting services.

# 06. Credits
- Some icons used are from the set [Squid Ink Line Icons](http://thesquid.ink/line-icons/)
