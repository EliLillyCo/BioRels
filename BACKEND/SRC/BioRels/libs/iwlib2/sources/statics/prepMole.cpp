#include "headers/statics/prepMole.h"
#include "headers/molecule/macromole.h"
#include "headers/statics/strutils.h"
#include "headers/iwcore/iwicf.h"
#include "headers/parser/readers.h"
#include "headers/proc/atomperception.h"
#include "headers/molecule/mmring_utils.h"
#include "headers/statics/protpool.h"
#include "headers/proc/dockmatch/dockmatch.h"
#include "headers/statics/logger.h"
#include "headers/iwcore/macrotoiw.h"

/**
 * @brief processInput
 * @param input
 * @param name
 * @param mole
 * @throw 250101   IWtoMacro                        Unsucessfull creation of SMILES
 * @throw 350102   MacroMole::addAtom               Bad allocation
 * @throw 352301   MacroMole::addRingSystem         No atom in the ring
 * @throw 352302   MacroMole::addRingSystem         Bad allocation
 * @throw 030401   MacroMole::getAtom               Given position is above the number of dots
 * @throw 310101   MMAtom::setAtomicName            Given atomic name must have 1,2 or 3 characters
 * @throw 310102   MMAtom::setAtomicName            Atomic name not found
 * @throw 310802   MMAtom::setMol2Type              Unrecognized MOL2 Type
 * @throw 350302   MacroMole::addAtom               Associated element to MOL2 is different than given element
 * @throw 350604   MacroMole::addBond               Both atoms are the same
 * @throw 351503   MacroMole::addResidue            Wrong given chain length.
 * @throw 351504   MacroMole::addResidue            Residue name empty
 * @throw 351901   MacroMole::getResidue            Given position is above the number of Residues
 * @throw 410101   ReadMOL2::load                   @<TRIPOS>MOLECULE block not found
 * @throw 410102   ReadMOL2::load                   Number of atoms not given in MOLECULE block
 * @throw 410103   ReadMOL2::load                   No ATOM block found while expecting atoms
 * @throw 410104   ReadMOL2::load                   No BOND block found while expecting bonds
 * @throw 410105   ReadMOL2::load                   No Residue block found while expecting Residues
 * @throw 410106   ReadMOL2::load                   Molecule is not owner
 * @throw 410107   ReadMOL2::load                   Error while loading file - Memory allocation issue
 * @throw 410202   ReadMOL2::readMOL2Substructure   Number of Residue found differs from expected Residues
 * @throw 410301   ReadMOL2::prepline               Wrong number of columns
 * @throw 410301   ReadMOL2::prepline               Wrong number of columns
 * @throw 410301   ReadMOL2::prepline               Wrong number of columns
 * @throw 410402   ReadMOL2::readMOL2Atom           Number of atoms found differs from expected atoms
 * @throw 410502   ReadMOL2::readMOL2Bond           Unrecognized bond type
 * @throw 410503   ReadMOL2::readMOL2Bond           Origin atom id is above the number of atoms
 * @throw 410504   ReadMOL2::readMOL2Bond           Target atom id is above the number of atoms
 * @throw 410505   ReadMOL2::readMOL2Bond           Number of bonds found differs from expected bond
 * @throw 410106   ReadMOL2::load                   Molecule is not owner
 * @throw 410107   ReadMOL2::load                   Error while loading file - Memory allocation issue
 * @throw 420501   ReadSDF::assignProp              Unexpectected end of line
 * @throw 420401   ReadSDF::loadAtom                Unexpectected end of line
 * @throw 420601   ReadSDF::load                    Unable to read file - Memory allocation issue
 * @throw 440101   Readers::load                    Unable to find extension
 * @throw 440201   Readers::load                    Unrecognized extension
 */
namespace protspace{
void processInput(const std::string& input,
                  const std::string& name,
                  MacroMole& mole)
try{
    size_t pos=input.find_last_of(".");
    if (pos == std::string::npos)
        protspace::SMILEStoMacroMole(input,name,mole);
    else
    {
        protspace::readFile(mole,input);
    }
    MacroToIW miw(mole);
    miw.generateRings();
    protspace::AtomPerception aperc;
    if (!aperc.perceive(mole))
        throw_line("2010301","processInput","Unable to perceive reference molecule");
}catch(ProtExcept &e)
{
    e.addHierarchy("processInput");
    e.addDescription("File involved : "+input);
    e.addDescription("Given Molecule name: "+name);
    throw;
}
void processInput(const std::string& input,
                  const std::string& name,
                  protspace::GroupList<protspace::MacroMole> & list)
try{
    size_t pos=input.find_last_of(".");
    if (pos == std::string::npos)
    {
        protspace::MacroMole *mole=new protspace::MacroMole();
        list.add(mole);
        protspace::SMILEStoMacroMole(input,name,*mole);
    }
    else
    {
        protspace::readFile(list,input);
    }
    for(size_t i=0;i<list.size();++i)
    {
        protspace::MacroMole& mole(list.get(i));
        if (mole.numAtoms()==0){list.remove(i);--i;continue;}
        MacroToIW miw(mole);
        miw.generateRings();
        protspace::AtomPerception aperc;
        if (!aperc.perceive(mole))
            throw_line("2010301","processInput","Unable to perceive reference molecule");
    }
}catch(ProtExcept &e)
{
    e.addHierarchy("processInput");
    e.addDescription("File involved : "+input);
    e.addDescription("Given Molecule name: "+name);
    throw;
}
}
