Using EasyAdmin with AI Coding Agents
=====================================

AI coding agents such as Claude Code, Codex, Cursor, GitHub Copilot, Gemini
CLI, JetBrains Junie and Windsurf write a good part of the EasyAdmin code of
many applications. Most of the EasyAdmin content they learned from describes
EasyAdmin 4, so they often generate code that doesn't work in EasyAdmin 5
(for example, calls to ``MenuItem::linkToCrud()``, which no longer exists).

To fix that, EasyAdmin ships an **agent skill**: a ``SKILL.md`` file and a
``references/`` directory that describe the EasyAdmin 5 API and the rules
agents must follow. The skill uses the `Agent Skills`_ open standard, which
all the agents listed above understand.

The skill is part of the Composer package, so it always describes the
EasyAdmin version installed in your application. You'll find it in the
``vendor/easycorp/easyadmin-bundle/skills/easyadmin/`` directory.

Agents load skills only from a few well-known directories of your project,
so you need to copy the skill there. EasyAdmin provides a command to do
that, as explained in the next section. The copies are real files and not
symbolic links, so they work on every operating system, including Windows.

The skill is plain text that you can read and change at any time. It doesn't
need network access and it doesn't send any telemetry.

Installing the Skill
--------------------

Run the following command in your Symfony application:

.. code-block:: terminal

    $ php bin/console easyadmin:ai:install

The command asks two questions. The first one is which AI coding agents you
want to install the skill for. You can select more than one and the agents
that your project already uses are preselected.

The second question asks if you want to add a short EasyAdmin section to
your AI instructions files (``CLAUDE.md`` and ``AGENTS.md``). The answer
defaults to yes and that section tells agents to read the skill before
working on your backend. See `The Project Instructions Block`_ for details.

When the command finishes, it lists all the files it wrote, reminds you to
restart your AI coding agent so it picks up the new skill, and shows the
Composer script that keeps the skill up to date automatically (see
`Keeping the Skill Up to Date`_).

.. note::

    If one of the selected directories already contains an ``easyadmin``
    skill that EasyAdmin didn't install, the command stops and writes
    nothing at all, not even for the other selected agents. Delete that
    directory and run the command again, or keep it and don't select that
    agent.

Supported AI Coding Agents
--------------------------

The install command supports the following targets. The option value is
the one you pass to ``--agent``, as explained in
`Installing Without Interaction`_:

=================================  ============  =====================  =================
Agents                             Option value  Skill directory        Instructions file
=================================  ============  =====================  =================
Claude Code                        ``claude``    ``.claude/skills/``    ``CLAUDE.md``
Codex, Cursor, Copilot and others  ``agents``    ``.agents/skills/``    ``AGENTS.md``
JetBrains Junie                    ``junie``     ``.junie/skills/``     ``AGENTS.md``
Windsurf                           ``windsurf``  ``.windsurf/skills/``  ``AGENTS.md``
=================================  ============  =====================  =================

The ``.agents/skills/`` directory is the shared location of the Agent Skills
standard. Codex, Cursor, GitHub Copilot, Gemini CLI, OpenCode, Amp and Zed
read their skills from it. Claude Code reads its instructions from
``CLAUDE.md`` instead of ``AGENTS.md``. Gemini CLI reads ``GEMINI.md`` by
default, so it ignores the block added to ``AGENTS.md`` unless you configure
it to load that file too, in the ``.gemini/settings.json`` file of your
project:

.. code-block:: json

    {
        "context": {
            "fileName": ["AGENTS.md", "GEMINI.md"]
        }
    }

To preselect the agents in the first question, the command looks for the
following files and directories in your project:

* Claude Code: ``.claude/`` or ``CLAUDE.md``;
* Codex, Cursor, GitHub Copilot, Gemini CLI, OpenCode and other agents:
  ``.agents/``, ``.codex/``, ``.cursor/``, ``.gemini/``, ``opencode.json``
  or ``.github/copilot-instructions.md``;
* JetBrains Junie: ``.junie/``;
* Windsurf: ``.windsurf/``.

When the command doesn't find any of them, it preselects Claude Code and the
shared ``.agents/skills/`` directory.

.. tip::

    Junie reads skills from both ``.junie/skills/`` and ``.agents/skills/``,
    so the ``junie`` target is a convenience and not a requirement.

Installing Without Interaction
------------------------------

In scripts and continuous integration workflows you can select the agents
with command options instead of answering questions:

.. code-block:: terminal

    # install the skill for one or more agents
    $ php bin/console easyadmin:ai:install --agent=claude --agent=agents

    # install the skill for all the supported agents
    $ php bin/console easyadmin:ai:install --all

    # install the skill for the detected agents without asking anything
    $ php bin/console easyadmin:ai:install --no-interaction

The ``--agent`` option can be used multiple times and accepts the values
``claude``, ``agents``, ``junie`` and ``windsurf``. Any other value makes
the command fail and display the list of valid values.

Used on its own, the ``--no-interaction`` option installs the skill for the
detected agents, or for Claude Code and the shared ``.agents/skills/``
directory when it detects none.

The ``--agent`` and ``--all`` options only skip the first question. The
command still asks the second one, unless you add ``--no-interaction``,
which accepts its default answer and adds the EasyAdmin section to your AI
instructions files. Add the ``--skip-guidelines`` option to leave
``CLAUDE.md`` and ``AGENTS.md`` untouched:

.. code-block:: terminal

    $ php bin/console easyadmin:ai:install --all --skip-guidelines

Keeping the Skill Up to Date
----------------------------

The copies of the skill describe the EasyAdmin version that was installed
when you created them. Run the following command after every EasyAdmin
update to refresh those copies:

.. code-block:: terminal

    $ php bin/console easyadmin:ai:update

This command never asks anything and never creates new files. It refreshes
only the copies that are already installed and it updates the instructions
block only in the files that already contain it. To install the skill for an
agent that you didn't select before, run ``easyadmin:ai:install`` again.

The update command displays no output and returns success when the skill
isn't installed anywhere. That's why it's safe to run it automatically after
every Composer update. Add it to the ``post-update-cmd`` script of your
``composer.json`` file, keeping the entries that you already have there:

.. code-block:: json

    {
        "scripts": {
            "post-update-cmd": [
                "@auto-scripts",
                "@php bin/console easyadmin:ai:update"
            ]
        }
    }

The command is also silent when deploying to production. Composer defines
the ``COMPOSER_DEV_MODE`` environment variable with a value of ``0`` when
running ``composer install --no-dev``, and the command exits immediately in
that case.

If you prefer to run the update yourself, add the ``--check`` option to your
continuous integration workflow:

.. code-block:: terminal

    $ php bin/console easyadmin:ai:update --check

This option writes nothing. It returns ``0`` when all the installed copies
and instructions blocks match the EasyAdmin version of the project, and
``1`` when any of them is outdated, which makes the build fail until someone
runs the update command. The check compares the contents of the files and
not the version numbers, so a copy that you changed by hand also counts as
outdated.

The Project Instructions Block
------------------------------

Claude Code reads the instructions of your project from a file called
``CLAUDE.md`` and the other agents read them from a file called
``AGENTS.md``. When you answer yes to the second question of the install
command, EasyAdmin adds a short section to those files, delimited by two
markers:

.. code-block:: markdown

    <easyadmin-guidelines>
    This project uses EasyAdmin 5.5.2. Before creating or modifying admin
    dashboards, CRUD controllers, fields, actions, filters or their tests,
    read and follow the `easyadmin` skill at
    `.claude/skills/easyadmin/SKILL.md`.
    ...
    </easyadmin-guidelines>

EasyAdmin owns the text between those markers and nothing else. The rest of
the file, including your own instructions, is left exactly as it is. When
the file doesn't exist, the install command creates it. The update command
never creates it.

Both commands replace the contents of the block on every run, so don't edit
the text inside the markers. Write your own instructions outside of the
block, as explained in the next section.

.. note::

    The commands replace the first pair of markers found in the file. Don't
    write those markers anywhere else in the file, not even inside a code
    block of your own.

Adding Your Own Conventions
---------------------------

The skill describes how EasyAdmin works, but it knows nothing about the
conventions of your project. Write them in a section titled
``## EasyAdmin conventions`` of your ``CLAUDE.md`` or ``AGENTS.md`` file,
outside the markers of the EasyAdmin block:

.. code-block:: markdown

    <easyadmin-guidelines>
    ...
    </easyadmin-guidelines>

    ## EasyAdmin conventions

    - All CRUD controllers live in `src/Controller/Admin/` and their tests
      in `tests/Admin/Controller/`.
    - Prices are stored in cents and displayed with `MoneyField`.
    - Never display the `password` property of the `User` entity.

The skill tells agents to read that section and to apply it on top of the
EasyAdmin rules. Because the section is outside the markers, the install and
update commands never change it.

Taking Ownership of the Skill
-----------------------------

The files copied to your project include two extra lines in the ``metadata``
section of their front matter:

.. code-block:: yaml

    # .claude/skills/easyadmin/SKILL.md
    ---
    name: easyadmin
    # ...
    metadata:
      author: EasyCorp
      easyadmin-version: '5.5.2'
      installed-by: 'easyadmin:ai:install'
    ---

The ``installed-by`` line tells EasyAdmin that it maintains this copy.
Delete that line to make the file yours: from that moment,
``easyadmin:ai:update`` ignores the copy and ``easyadmin:ai:install``
refuses to overwrite it and stops without writing anything.

Do this when you want to adapt the skill to your project. The drawback is
that you no longer get the improvements and fixes of new EasyAdmin versions,
so consider writing your changes in the ``## EasyAdmin conventions`` section
instead.

Version Control
---------------

Commit the installed files to your repository. Your teammates then get the
same skill without running any command and their agents behave like yours.
This is also what makes the ``--check`` option useful in continuous
integration.

Depending on the agents that you selected, these are the files to commit:

* ``.claude/skills/easyadmin/`` and ``CLAUDE.md``;
* ``.agents/skills/easyadmin/`` and ``AGENTS.md``;
* ``.junie/skills/easyadmin/``;
* ``.windsurf/skills/easyadmin/``.

.. _`Agent Skills`: https://agentskills.io
