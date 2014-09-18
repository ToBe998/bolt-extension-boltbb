[BETA] BoltBB Extension for Bolt
================================

BoltBB is a simple discussion forum extension for Bolt.

The base install has templates that you can adapt for your own theme, and is base off Bolt's 
`base-2014` theme.

Dependencies
------------

BoltBB requires the following Bolt extensions to be installed and configured:
  * Client Login - `bolt/clientlogin`
  * Members      - `bolt/members`
  
Please see the documentation for each of those for more information on configuring them for your site.

Initial Set Up
--------------

#### Defining Forums

The first step is to edit your `app/config/extensions/BoltBB.yml` file and define the forums you want 
to use.

An example would be:

```
forums:
  kittens:
    title: Kittens Forum
    description: A place to discuss kittens and how cute they are
  puppies:
    title: Puppies Forum
    description: Puppie-piles and dog biscuits
```

#### Contenttypes

Second step it to define the contenttypes used for topics and replies.

Navigate to **Extras/BoltBB** in the admin panel and you will notice a list of available buttons in 
the right sidebar.

Click the 'Setup Contenttypes' button.  This will check your contenttypes.yml file for existing 
contentypes, and add them if not found.  

**NOTE:** This button will disappear once they are added. 

#### Tables

Now you need to update Bolt's database to add missing tables for BoltBB to function.

Navigate to **Configuration/Check database** and Bolt will tell you what tables are missing, you just 
need to do a normal update.


#### Creating Forum Records

The final stage of the initial set up is to add the forum records to the database table.

Navigate to **Extras/BoltBB** and on the right sidebar, under **Forum Setup** click the 'Sync Table' 
button.

#### Base Template

BoltBB will work out-of-the-box at this point if you're using the `base-2014` theme, or one that has 
a `_header.twig`, `_footer.twig` and `_aside.twig`.

To override this, you simply need to create a file called `boltbb.twig` in your theme's directory and 
include the following block statement where you want the forums rendered:

```
{% block forums %}
{% endblock forums %}
```

**NOTE:** The file name for the base template can be set in your `BoltBB.yml` file:

```
templates:
  parent: boltbb.twig
```

#### Individual Templates (optional)

You can override the individual Twig templates for the following:
  * Forums
    * Index page (defaults to `boltbb_index.twig`)
    * Forum (defaults to `boltbb_forum.twig`)
    * Topic (defaults to `boltbb_topic.twig`)
  * Email notification 
    * Subject lines (defaults to `boltbb_email_subject.twig`)
    * Email body (defaults to `boltbb_email_body.twig`)
  * Navigation breadcrumbs (defaults to `boltbb_breadcrumbs.twig`)

You can copy these files to your theme directory and edit them to better match your desired layout.

For the forums templates, at a minimum you should ensure the following exists in any new custom twig 
files you create:

  * To inherit the `boltbb.twig` template 
```
{% extends twigparent %}
```

  * The block that layout will be rendered in by Twig
```
{% block forums %}
   <!-- Your HTML & Twig here -->
{% endblock forums %}
```

**NOTE:** To use different names, and/or subdirectories in your theme directory, see the `templates:` 
section in your `BoltBB.yml` file.

#### Complete!

You should now be able to access your forums at `http://example.com/forums` 


Managing Forums
---------------

Currently there are two options for forums:
    * `open`   - Forum is open for new topics
    * `closed` - Forum is closed to new topics & replies

The impact of these states are handled in their associated Twig template files.

To change the state of a forum, navigate to **Extras/BoltBB** in the admin panel, select the forum(s) 
you want to change and click either the 'Open Selected Forum', or 'Close Selected Forum' button in 
the **'Maintenance'** section in the right sidebar.

Managing Topics
---------------

Both topics and relies are normal Bolt contenttypes, and as such you can edit them in the same manner 
as any content in Bolt.

Specific record properties that can be changed in the record editor for a particular
topic:
  * State
    * `open`   - Topic is open for further replies
    * `closed` - Topic is closed to new replies
  * Visibility
    * `normal` - Topic will show up in 'newest first' date order in the specific forum
    * `pinned` - Topic will always show at the top of the forum
    * `global` - Topic will show at the top of **all** forums, analogous to a global notice
