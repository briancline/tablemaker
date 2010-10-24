# Table Maker

Table maker is an extremely simple compiler that reads in a database spec file 
containing a list of tables, then generates output that can be used to create 
those tables in MySQL.

It is not yet intended to provide full support for all features available in 
MySQL but is certainly enough for most projects. It can be automated as part of
a build process with a simple Makefile.

All tables by default will use the utf8 character set, the MyISAM engine, and
output will include `DROP IF EXISTS` statements for each table.


## Build Script

The build.php script compiles the specified filename and sends the resulting
SQL to STDOUT. Building the accompanying sample spec file into compiled MySQL
file can be performed with the following command:

    $ ./build.php sample.in > sample.sql


## Spec File Format

Spec files are plain text files that, at minimum, contain a table name on its
own line, followed by a series of consecutive tab-indented lines each 
representing column data types and names. Each column line is written as the
data type shorthand preceded by optional modifier characters (see lists below), 
followed by a colon, and the name of the field. No spaces should separate each
element of the field spec line, but spaces can be used in the column name and
will be converted to underscores during compilation.


### Column Data Types (Shorthand)
The following shorthand data types should be used in the spec file.

    Shorthand     MySQL Data Type    Default Modifiers
    -----------   ----------------   -----------------
    bi            bigint             Unsigned, non-nullable
    ti            tinyint            Unsigned, non-nullable
    i             int                Unsigned, non-nullable
    d             decimal            Unsigned, non-nullable
    c             char               Non-nullable
    v             varchar            Non-nullable
    t             text               Non-nullable
    dt            datetime           Non-nullable


### Column Modifiers

You may optionally use these column modifiers before a to specify various MySQL 
column options.

    Shorthand      MySQL Behavior
    ------------   --------------
    *              Sets column as the primary key
    +              Enables auto-increment for the column
    -              Turns integer type into signed (all are unsigned by default)
    ^              Allows NULL values (all are non-nullable by default)
    x              Sets column length to x (accepts any integer)
    x.y            Sets decimal column length to allow for x digits before 
                   decimal point, with y decimal places after
    (abc)          Sets the default value for the column to 'abc'


### Macros

Table Maker supports the use of macros to define data types and modifiers that
are used repeatedly throughout the spec file. This allows for easier maintenance
later when one needs to expand the length of columns containing certain kinds
of data (emails, names, monetary amounts, etc).

Macros follow the syntax `#define x y`, where x is the alias you wish to use,
and y is the data type and modifier shorthands `x` should map to. Macros should 
be defined at the top of the spec file thusly:

    #define id *+bi
    #define hash c32
    #define name v30
    #define email v50

They can then be used as the entire data type name of a column, mixed with other 
non-macro types within the same table and modifiers within the same line.



## Examples
A simple database with a `user` table might have one such spec file:

    user
    	*+bi:user id
    	v50:email
    	c32:password
    	v30:first name
    	v30:last name
    	-i(50):credits
    	-d5.4:average
    	dt:create date

With a few macros we can standardize what constitutes an auto-incrementing
primary key ID column, a password hash, email address, and a hypothetical 
credit count, so these definitions can be used across other tables and easily
modified across the entire database later:

    #define id *+bi
    #define hash c32
    #define email v50
    #define credit i
    
    user
    	id:user id
    	email:email
    	hash:password
    	v30:first name
    	v30:last name
    	-credit(50):credits
    	-d5.4:average
    	dt:create date

When compiled by the `build.php` script, the resulting SQL is generated:

    DROP TABLE IF EXISTS `user`;
    CREATE TABLE `user` (
    	`user_id` bigint unsigned NOT NULL AUTO_INCREMENT,
    	`email` varchar(50) NOT NULL,
    	`password` char(32) NOT NULL,
    	`first_name` varchar(30) NOT NULL,
    	`last_name` varchar(30) NOT NULL,
    	`credits` int NOT NULL DEFAULT '50',
    	`average` decimal(9,4) NOT NULL,
    	`create_date` datetime NOT NULL,
    	PRIMARY KEY (`user_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8;

## Other Notes

I use Table Maker for most of the projects I get involved in that require a 
clean MySQL database, as it speeds up the database spec process quite a bit and
allows me to better automate the database and table rebuild process in a 
development environment.

While it is nowhere near what I would call "complete," nor as robust as I would
eventually like it to be, I constantly make improvements and enhancements to it
when the need for those come up.

If you have suggestions or would like to contribute to make it even better, 
drop me a message on [Github][1].

  
  [1]:http://github.com/briancline

