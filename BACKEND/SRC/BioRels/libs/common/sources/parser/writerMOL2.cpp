#include <sstream>
#include <unistd.h>
#include <iomanip>
#ifdef WINDOWS
#include <windows.h>
#include <lmcons.h>
#endif
#ifdef LINUX
#include <pwd.h>
#endif
#include "headers/parser/writerMOL2.h"
#include "headers/molecule/macromole.h"
#include"headers/statics/intertypes.h"
#include "headers/molecule/mmresidue_utils.h"
#include "headers/statics/strutils.h"
#include "headers/parser/string_convert.h"
#undef NDEBUG /// Active assertion in release

protspace::WriteMOL2::WriteMOL2():WriterBase(),
    mOutputDate(true),
    mOutputUserID(false)
{

}


protspace::WriteMOL2::WriteMOL2(const std::string& path, const bool& onlySelected):
    WriterBase(path,onlySelected),
    mOutputDate(true),
    mOutputUserID(false)
{

}

void protspace::WriteMOL2::outputHeader(const MacroMole& molecule)
{
    if(mOutputDate)
    {
//    mOfs <<"# Creation date "<< protspace::getTime()<<"\n";
    }
    if(mOutputUserID)
    {
#ifdef LINUX
    const struct passwd *pws(getpwuid(geteuid()));
    mOfs <<"# By: "<<pws->pw_name<<"\n";
#endif
#ifdef WINDOWS
        char username[UNLEN+1];
        DWORD username_len =UNLEN+1;
        GetUserName(username,&username_len);
        mOfs << "# By :"<< username<<"\n";
#endif
    }
    mOfs << "@<TRIPOS>MOLECULE\n"
         << ((molecule.getName().length()==0)?"unknown":molecule.getName())<<"\n"
         << mAtomToConsider.size()  << " "
         << mBondToConsider.size()  << " "
         << mResidueToConsider.size() << " "
         << "0 " // Number of features in the molecule
         << "0 " // Number of sets in the molecule
         << "\n";
    switch (molecule.getMoleType())
    {

    case MOLETYPE::PROTEIN:
        mOfs << "PROTEIN\n";break;
    default:
        mOfs << "SMALL\n";break;

    }
    mOfs << "USER_CHARGES\n"
         << "\n" // status_bits
         << "\n";// mol_comment

}


/**atom_id atom_name x y z atom_type [subst_id [subst_name [charge [status_bit]]]]
  * • atom_id (integer) = the ID number of the atom at the time the file was
  *   created. This is provided for reference only and is not used when the
  *   .mol2 file is read into SYBYL.
  * • atom_name (string) = the name of the atom.
  * • x (real) = the x coordinate of the atom.
  * • y (real) = the y coordinate of the atom.
  * • z (real) = the z coordinate of the atom.
  * • atom_type (string) = the SYBYL atom type for the atom.
  * • subst_id (integer) = the ID number of the substructure containing the
  *   atom.
  * • subst_name (string) = the name of the substructure containing the atom.
  * • charge (real) = the charge associated with the atom.
  * • status_bit (string) = the internal SYBYL status bits associated with the
  *   atom. These should never be set by the user. Valid status bits are
  *   DSPIOD, TYPECOL, CAP, BACKBONE, DICT, ESSENTIAL, WATER and
  *   DIRECT.
  */
void protspace::WriteMOL2::outputAtom(const MacroMole& mole)
try{

    mOfs << "@<TRIPOS>ATOM\n";
    std::ostringstream oss;size_t posRes;
    for (size_t iAtm=0;iAtm < mAtomToConsider.size();++iAtm)
    {
        const MMAtom& atom = mole.getAtom(mAtomToConsider.at(iAtm));
        const MMResidue& res=atom.getResidue();
        if (!getResiduePos(res.getMID(),posRes))
        {
            throw_line("460101",
                       "WriterMOL2::outputAtom",
                       "Unable to find residue");
            continue;
        }
        mOfs << std::right << std::setw(7) << (iAtm+1)<<" "
             << std::left  << std::setw(6) << atom.getName() << " ";
        mOfs<<std::right
           <<std::setw(10)
          <<std::fixed
         <<std::setprecision(4)
        <<atom.pos().x()<<" "
        <<std::right
        <<std::setw(10)
        <<std::fixed
        <<std::setprecision(4)
        <<atom.pos().y()<<" "
        <<std::right
        <<std::setw(10)
        <<std::fixed
        <<std::setprecision(4)
        <<atom.pos().z();

        oss.str("");
        oss<<res.getName()<<res.getFID();


        mOfs << " "
             << std::left <<std::setw(5)<<atom.getMOL2()<<" "
             << std::right<<std::setw(5)<<posRes+1
             << " "
             << std::left << std::setw(9)<<oss.str()
             << " "
             << std::setw(7) <<std::fixed<<std::setprecision(4)
             <<(double)atom.getFormalCharge()<<"\n";
    }
}catch(ProtExcept &e)
{
    if (e.getId()=="460101")throw;

    /// mole.getAtom shouldn't failt
    assert(e.getId()!="030401");
    assert(e.getId()!="310701");
}

void protspace::WriteMOL2::save(const MacroMole& mole)
try{
    if (!mOfs.is_open())open();
    selectObjects(mole);
    outputHeader(mole);
    outputAtom(mole);
    if (!mBondToConsider.empty())outputBond(mole);
    if (!mResidueToConsider.empty())outputResidue(mole);
    std::flush(mOfs);
}catch(ProtExcept &e)
{
    e.addHierarchy("WriterMOL2::save");
    throw;
}

void protspace::WriteMOL2::outputBond(const MacroMole& mole)
try{
    std::map<size_t,size_t> mapps;
    for (size_t iAtm=0;iAtm < mAtomToConsider.size();++iAtm)
    {
        const MMAtom& atom = mole.getAtom(mAtomToConsider.at(iAtm));
        mapps[atom.getMID()]=iAtm;
    }
    size_t posAtm1, posAtm2;
    mOfs << "@<TRIPOS>BOND\n";

    for (size_t iBond=0;iBond < mBondToConsider.size();++iBond)
    {
        const MMBond& bond = mole.getBond(mBondToConsider.at(iBond));
        const auto it1=mapps.find(bond.getAtom1().getMID());
        if (it1==mapps.end())
            throw_line("460201",
                       "WriteMOL2::outputBond",
                       "Unable to find atom 1\n"+bond);
        const auto it2=mapps.find(bond.getAtom2().getMID());
        if (it2==mapps.end())
            throw_line("460202",
                       "WriteMOL2::outputBond",
                       "Unable to find atom 2\n"+bond);
        posAtm1=(*it1).second;
        posAtm2=(*it2).second;
//        if (!getAtomPos(bond.getAtom1().getMID(),posAtm1))
//        {
//            throw_line("460201",
//                       "WriteMOL2::outputBond",
//                       "Unable to find atom 1\n"+bond);
//        }
//        if (!getAtomPos(bond.getAtom2().getMID(),posAtm2))
//        {
//            throw_line("460202",
//                       "WriteMOL2::outputBond",
//                       "Unable to find atom 2\n"+bond);
//        }
        mOfs << std::right << std::setw(6) << (iBond+1)<<" "
             << std::left  << std::setw(7) << (posAtm1+1)<< " "
             <<std::left << std::setw(7) << (posAtm2+1) << " ";
        const auto it=BOND::typeToMOL2.find(bond.getType());
        if (it==BOND::typeToMOL2.end())
            throw_line("460203",
                       "WriteMOL2::outputBond",
                       "Unrecognized bond type");
        mOfs<<(*it).second<<"\n";


    }

}catch(ProtExcept &e)
{
    assert(e.getId()!="350501");
    e.addHierarchy("WriteMOL2::outputBond");
    throw;
}


void protspace::WriteMOL2::outputResidue(const MacroMole& mole)
try{


    mOfs << "@<TRIPOS>SUBSTRUCTURE\n";
    size_t atmpos;
    std::vector<MMResidue*> list;list.reserve(5);
    for (size_t iRes=0;iRes < mResidueToConsider.size();++iRes)
    {

        try{
            const MMResidue& residue = mole.getResidue(mResidueToConsider.at(iRes));

            mOfs << std::right << std::setw(6) << (iRes+1)<<" ";
            std::ostringstream ossx; ossx<<residue.getName()<<residue.getFID();
            mOfs<< std::left <<std::setw(7)<<ossx.str()<<" ";
            if (residue.getResType()==(RESTYPE::STANDARD_AA)
                    ||residue.getResType()==(RESTYPE::MODIFIED_AA))
            {
                bool found=false;
                for (size_t iAtm=0; iAtm < residue.numAtoms();++iAtm)
                {
                    const MMAtom& atom=residue.getAtom(iAtm);
                    if (atom.getName()=="CA" &&
                        getAtomPos(atom.getMID(),atmpos))
                    {
                        mOfs  << std::setw(5) << atmpos+1<<" ";
                        found =true;break;
                    }
                }
                if (!found && getAtomPos(residue.getAtom(0).getMID(),atmpos))
                    mOfs  << std::setw(5) <<atmpos+1<<" ";
            }
            else
            {
                 getAtomPos(residue.getAtom(0).getMID(),atmpos);
                mOfs   << std::setw(5) << (atmpos+1) <<" ";
            }
            mOfs<< "RESIDUE 1 "<< ((residue.getChainName()=="")?"X":residue.getChainName())
                << " "<< std::setw(3)<<residue.getName();
            list.clear();
            getLinkedResidue(residue,list);

            mOfs<< " "<<list.size()<<"\n";
        }catch(ProtExcept &e)
        {throw;}


    }
    mOfs<<"\n";
}catch(ProtExcept &e)
{
    ///Residue must be found
    assert(e.getId()!="351901");
    /// Atom must be found
    assert(e.getId()!="320501" &&e.getId()!="320502");
    e.addHierarchy("WriteMOL2::outputResidue");
    throw;
}
