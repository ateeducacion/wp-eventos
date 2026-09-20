<?php
/**
 * Ordered load list for the modular EVT events application.
 *
 * Single source of truth: `bootstrap.php` requires these files for tests and
 * the dev environment, and `build/pack-snippet.php` inlines them in this same
 * order into the Code Snippets bundle. A new file under `src/Evt/` must be
 * added here or the bundler fails.
 *
 * Paths are relative to this directory. Order matters: a class must come after
 * everything it extends or uses at load time, and `App.php` goes last. The
 * first entry has to start with its `namespace` statement: the bundler injects
 * the EVT_BUNDLE_LOADED guard right after it.
 *
 * @package Evt
 */

return array(
	'Meta/EventMetaKeys.php',
	'Meta/EventMetaRegistration.php',
	'Meta/ProgrammeMetaKeys.php',
	'Meta/ProgrammeMetaRegistration.php',
	'Meta/RegistrationMetaKeys.php',
	'Domain/DateRange.php',
	'Domain/ActivityInput.php',
	'Domain/SignupQuestions.php',
	'Domain/RegistrationInput.php',
	'Domain/EventInput.php',
	'Domain/EventState.php',
	'Access/EventAccess.php',
	'PostType/EventPostType.php',
	'PostType/SpeakerPostType.php',
	'PostType/ActivityPostType.php',
	'PostType/RegistrationPostType.php',
	'PublicFront/ExitSignal.php',
	'Centre/CentreCatalogue.php',
	'Centre/CentreCatalogueSync.php',
	'Centre/CentreCatalog.php',
	'Meta/RegistrationMetaRegistration.php',
	'Taxonomy/EventTaxonomies.php',
	'PublicFront/Assets.php',
	'PublicFront/Shell.php',
	'PublicFront/EditLock.php',
	'PublicFront/CodeEditor.php',
	'PublicFront/CustomCode.php',
	'PublicFront/EventList.php',
	'PublicFront/View/EventListView.php',
	'PublicFront/Programme.php',
	'PublicFront/Participants.php',
	'PublicFront/Registrations.php',
	'PublicFront/RegistrationFiles.php',
	'PublicFront/SignupForm.php',
	'PublicFront/EventWorkspace.php',
	'PublicFront/View/PanelParts.php',
	'PublicFront/View/EventSpeakersPanel.php',
	'PublicFront/View/EventProgrammePanel.php',
	'PublicFront/View/EventWorkshopsPanel.php',
	'PublicFront/View/EventParticipantsPanel.php',
	'PublicFront/View/EventSignupPanel.php',
	'PublicFront/View/EventSectionsPanel.php',
	'PublicFront/View/EventDataPanel.php',
	'PublicFront/View/EventAppearancePanel.php',
	'PublicFront/View/EventCodePanel.php',
	'PublicFront/View/EventWorkspaceView.php',
	'PublicFront/PageForm.php',
	'PublicFront/View/PageFormView.php',
	'PublicFront/Block/ContentBlock.php',
	'PublicFront/Block/PosterBlock.php',
	'PublicFront/Block/SectionsBlock.php',
	'PublicFront/Block/SignupBlock.php',
	'PublicFront/EventLayout.php',
	'PublicFront/EventView.php',
	'PublicFront/View/EventChrome.php',
	'PublicFront/Home.php',
	'Admin/EventAdmin.php',
	'Admin/Settings.php',
	'App.php',
);
