<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Ai;

/**
 * The state of the EasyAdmin skill files installed for some AI coding agent.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
enum SkillStatus
{
    case NotInstalled;
    case UpToDate;
    case Outdated;
    case NotManagedByEasyAdmin;
}
