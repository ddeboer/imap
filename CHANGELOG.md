# Change Log

## [0.5.2](https://github.com/ddeboer/imap/tree/0.5.2) (2015-12-03)
[Full Changelog](https://github.com/ddeboer/imap/compare/0.5.1...0.5.2)

**Closed issues:**

- $message-\>getAttachments\(\) returns null if message has no attachments [\#80](https://github.com/ddeboer/imap/issues/80)
- Email objects visibility [\#76](https://github.com/ddeboer/imap/issues/76)

**Merged pull requests:**

- Fixed the keepUnseen method [\#95](https://github.com/ddeboer/imap/pull/95) ([aeyoll](https://github.com/aeyoll))
- Mark Mailbox as countable, fix doc comments [\#91](https://github.com/ddeboer/imap/pull/91) ([krzysiekpiasecki](https://github.com/krzysiekpiasecki))
- Message::getAttachments confirm to signature [\#82](https://github.com/ddeboer/imap/pull/82) ([boekkooi](https://github.com/boekkooi))
- Added hasMailbox to Connection [\#81](https://github.com/ddeboer/imap/pull/81) ([boekkooi](https://github.com/boekkooi))
- Make sure imap connection are reopened [\#79](https://github.com/ddeboer/imap/pull/79) ([joserobleda](https://github.com/joserobleda))

## [0.5.1](https://github.com/ddeboer/imap/tree/0.5.1) (2015-02-01)
[Full Changelog](https://github.com/ddeboer/imap/compare/0.5.0...0.5.1)

**Closed issues:**

- imap\_open error  [\#72](https://github.com/ddeboer/imap/issues/72)
- $message-\>getAttachments\(\) does not return anything, even though a message has at least one attachment [\#71](https://github.com/ddeboer/imap/issues/71)
- Prepare docs for 1.0 [\#69](https://github.com/ddeboer/imap/issues/69)
- "date" header is not reliable [\#63](https://github.com/ddeboer/imap/issues/63)
- File Attachments don't show up [\#55](https://github.com/ddeboer/imap/issues/55)

**Merged pull requests:**

- Add support for attachments without content disposition [\#70](https://github.com/ddeboer/imap/pull/70) ([ddeboer](https://github.com/ddeboer))

## [0.5.0](https://github.com/ddeboer/imap/tree/0.5.0) (2015-01-24)
[Full Changelog](https://github.com/ddeboer/imap/compare/0.4.0...0.5.0)

**Closed issues:**

- Use utf8\_encode\(\) function to encode content [\#66](https://github.com/ddeboer/imap/issues/66)
- Please add function order by date [\#59](https://github.com/ddeboer/imap/issues/59)
- mb\_convert\_encoding breaks code [\#57](https://github.com/ddeboer/imap/issues/57)
- How get I getMessages but newest first ... [\#11](https://github.com/ddeboer/imap/issues/11)

## [0.4.0](https://github.com/ddeboer/imap/tree/0.4.0) (2015-01-04)
[Full Changelog](https://github.com/ddeboer/imap/compare/0.3.1...0.4.0)

**Closed issues:**

- Please add 6th parameter to imap\_open call [\#62](https://github.com/ddeboer/imap/issues/62)
- Should Message::delete\(\) use the Message UID? [\#46](https://github.com/ddeboer/imap/issues/46)
- mb\_convert\_encoding\(\): Illegal character encoding specified [\#35](https://github.com/ddeboer/imap/issues/35)
- Deleting a message isn't working [\#30](https://github.com/ddeboer/imap/issues/30)
- imap\_header doesn't work with message uid [\#26](https://github.com/ddeboer/imap/issues/26)

**Merged pull requests:**

- Added basic requirement [\#61](https://github.com/ddeboer/imap/pull/61) ([nikoskip](https://github.com/nikoskip))
- FIX: PHP error: "Cannot declare class Ddeboer\Imap\Search\Text\Text ..." [\#58](https://github.com/ddeboer/imap/pull/58) ([racztiborzoltan](https://github.com/racztiborzoltan))
- Message::delete sets the FT\_UID flag.  Fixes \#30 Fixes \#46 [\#54](https://github.com/ddeboer/imap/pull/54) ([ctalbot](https://github.com/ctalbot))
- Allow binary-encoded part content [\#48](https://github.com/ddeboer/imap/pull/48) ([joker806](https://github.com/joker806))
- Fix CS [\#47](https://github.com/ddeboer/imap/pull/47) ([xelan](https://github.com/xelan))
- fixed typo [\#45](https://github.com/ddeboer/imap/pull/45) ([xelan](https://github.com/xelan))

## [0.3.1](https://github.com/ddeboer/imap/tree/0.3.1) (2014-08-11)
[Full Changelog](https://github.com/ddeboer/imap/compare/0.3.0...0.3.1)

**Merged pull requests:**

- \imap\_header dosen't work with UID [\#44](https://github.com/ddeboer/imap/pull/44) ([ysramirez](https://github.com/ysramirez))

## [0.3.0](https://github.com/ddeboer/imap/tree/0.3.0) (2014-08-10)
[Full Changelog](https://github.com/ddeboer/imap/compare/0.2...0.3.0)

**Closed issues:**

- please remove useless wiki [\#42](https://github.com/ddeboer/imap/issues/42)
- Travis tests allways fail? [\#40](https://github.com/ddeboer/imap/issues/40)
- Garbled e-mail body encoding [\#27](https://github.com/ddeboer/imap/issues/27)
- Improve docs [\#25](https://github.com/ddeboer/imap/issues/25)
- "undisclosed-recipients" throws error [\#23](https://github.com/ddeboer/imap/issues/23)

**Merged pull requests:**

- correct minor typo [\#43](https://github.com/ddeboer/imap/pull/43) ([cordoval](https://github.com/cordoval))
- Utf-8 encode body content. [\#39](https://github.com/ddeboer/imap/pull/39) ([cmoralesweb](https://github.com/cmoralesweb))
- Fix regex parsing the date header \(allowing multiple brackets\) [\#38](https://github.com/ddeboer/imap/pull/38) ([joker806](https://github.com/joker806))
- Allow empty connection flags [\#34](https://github.com/ddeboer/imap/pull/34) ([joker806](https://github.com/joker806))
- Fixed typo [\#32](https://github.com/ddeboer/imap/pull/32) ([abhinavkumar940](https://github.com/abhinavkumar940))

## [0.2](https://github.com/ddeboer/imap/tree/0.2) (2013-11-24)
[Full Changelog](https://github.com/ddeboer/imap/compare/0.1...0.2)

## [0.1](https://github.com/ddeboer/imap/tree/0.1) (2013-11-22)
**Closed issues:**

- Prevent setting SEEN flag [\#20](https://github.com/ddeboer/imap/issues/20)
- Add tests [\#18](https://github.com/ddeboer/imap/issues/18)
- delete messages [\#9](https://github.com/ddeboer/imap/issues/9)
- README is missing basic usage [\#7](https://github.com/ddeboer/imap/issues/7)
- Subject and other texts are decoded incorrectly  [\#3](https://github.com/ddeboer/imap/issues/3)

**Merged pull requests:**

- also fetch inline attachments [\#24](https://github.com/ddeboer/imap/pull/24) ([kaiserlos](https://github.com/kaiserlos))
- since leading slash is always needed [\#22](https://github.com/ddeboer/imap/pull/22) ([huglester](https://github.com/huglester))
- Added missed createMailbox\($name\) function [\#19](https://github.com/ddeboer/imap/pull/19) ([burci](https://github.com/burci))
- Added move and delete function to message + expunge function [\#17](https://github.com/ddeboer/imap/pull/17) ([burci](https://github.com/burci))
- Clean up some unused variable [\#16](https://github.com/ddeboer/imap/pull/16) ([burci](https://github.com/burci))
- Fixed mailbox encoding [\#15](https://github.com/ddeboer/imap/pull/15) ([burci](https://github.com/burci))
- Create new mailbox [\#14](https://github.com/ddeboer/imap/pull/14) ([burci](https://github.com/burci))
- Fixed bug in getDecodedContent with 'format=flowed' email [\#13](https://github.com/ddeboer/imap/pull/13) ([burci](https://github.com/burci))
- Fixed date parsing for some imap servers [\#12](https://github.com/ddeboer/imap/pull/12) ([thelfensdrfer](https://github.com/thelfensdrfer))
- Add support for more complex search expressions. [\#10](https://github.com/ddeboer/imap/pull/10) ([jamesiarmes](https://github.com/jamesiarmes))
- Allow user to change server connection flags [\#6](https://github.com/ddeboer/imap/pull/6) ([mvar](https://github.com/mvar))
- Improvements in EmailAddress class [\#4](https://github.com/ddeboer/imap/pull/4) ([mvar](https://github.com/mvar))



\* *This Change Log was automatically generated by [github_changelog_generator](https://github.com/skywinder/Github-Changelog-Generator)*