# Podcast RSS Editor
After hosting a design podcast, I started manually updating the RSS feed for every episode and quickly got sick of the 'tags dancing', so tried a little bit building this simple Podcast RSS editor in PHP for myself.

![](https://raw.githubusercontent.com/JJYing/Podcast-RSS-Editor/master/screenshot.jpg)

# Usage
- Put the XML file you wanna edit in the root and select it in the 'Settings' panel and then you can edit / add episodes.
- There isn't any authentication or security check so please don't put it on the sever in case shit happens.

# To-do
- ~~'Add new item' function~~
- 'Remove item' function
- ~~Cover image field~~
- ~~Edit in CDATA for item description~~
- Cover image preview
- Audio preview
- Put a logo
- Add more options
- ~~Seperate dashboard page with more data~~
- Make the publish time input as a data picker
- ~~Add an option to select XML file in settings section~~
- ~~Add message notifications~~
- Add default episode template in settings

# Known Issues
- After editing an episode, the page jumped to still shows the old data.
- When the duration is XX:XX, it failed to calculate the correct nubmer
- Timezone issue for 'today'
- Some people use different tag names in RSS file, like 'itunes:author' vs. 'autor'

# Alternatives
- [Itunes podcast XML generator](http://codepen.io/jon-walstedt/pen/jsIup) by Jon Wålstedt
- [Podcast Generator](http://www.podcastgenerator.net/)

# Credits
- Some icons used are from the set [Squid Ink Line Icons](http://thesquid.ink/line-icons/)