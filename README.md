# Markdown formatter for MantisBT
This plugin allows you to format notes in Mantis with Markdown, using [Parsedown](http://parsedown.org/).

Currently this decides whether to use the "old" format or Markdown based on a very simple rule:
Iff the text starts with '#MD#' then it will be Markdown formatted, otherwise MantisCoreFormatted.

As the core formatter formats the text unconditionally, the best results can be achieved only by disabling (delete at the `Manage > Plugins` page) it.
Sorry, haven't found better alternative yet.
