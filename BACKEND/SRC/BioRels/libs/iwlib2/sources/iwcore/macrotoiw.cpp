#include "headers/iwcore/macrotoiw.h"
#include "aromatic.h"
#include "iwstring_data_source.h"
#include "iwstandard.h"
#include "path.h"
#include "headers/statics/intertypes.h"
#include "headers/molecule/mmring_utils.h"
#include "headers/molecule/mmresidue.h"
using namespace std;

MacroToIW::MacroToIW(protspace::MacroMole& mole,
                     const bool& onlyUsed,
                     const bool& toGraph,
                     const std::map<protspace::MMAtom*, int>& isotopes,
                     const bool& wAromaticity):
    mMacro(mole),mOnlyUsed(onlyUsed),mWArom(wAromaticity),
    mToGraph(toGraph),
    arAtoms(new int[mole.numAtoms()]),
    arBonds(new int[mole.numBonds()])
{

set_global_aromaticity_type(Daylight);
set_kekule_try_positive_nitrogen(1);

    set_convert_chain_aromatic_bonds(1);
    set_invalidate_bond_list_ring_info_during_invalidate_ring_info(0);
for(size_t i=0;i<mole.numAtoms();++i)arAtoms[i]=false;
for(size_t i=0;i<mole.numBonds();++i)arBonds[i]=false;
    convertAtoms(isotopes);
    convertBonds();
    if (!mWArom)return;
    if (0== mIWMole.process_delocalised_carbonyl_bonds(arAtoms,arBonds))
        cerr <<"FAILURE"<<endl << mIWMole.smiles()<<endl;


    // cout << "RETURN KEKULE:"<<
//  cout <<"RETURN " <<
    mIWMole.find_kekule_form(arAtoms);

}

MacroToIW::~MacroToIW()
{
    delete[] arAtoms;
    delete[] arBonds;
}


void MacroToIW::convertAtoms(const std::map<protspace::MMAtom *, int> & isotopes)
try{
    size_t nAtm=0;
    for (size_t i=0; i< mMacro.numAtoms();++i)
    {
        protspace::MMAtom& macro_atom= mMacro.getAtom(i);
//        cout << macro_atom.getIdentifier()<<endl;
        if (mOnlyUsed && !macro_atom.isSelected())continue;
        if (macro_atom.isHydrogen())continue;
        if (macro_atom.getResidue().getResType()==RESTYPE::WATER)continue;
        if (macro_atom.getName()=="DuAr"||macro_atom.getName()=="DuCy")continue;

        Atom* atom = new Atom((mToGraph)?6:macro_atom.getAtomicNum());
        const auto it = isotopes.find(&macro_atom);
        if (it!= isotopes.end())atom->set_isotope((*it).second);
        mIWMole.add(atom);
        atom->setxyz(macro_atom.pos().x(),
                     macro_atom.pos().y(),
                     macro_atom.pos().z());


        if (macro_atom.getFormalCharge() >= -4 &&
            macro_atom.getFormalCharge() <=  4)
            atom->set_formal_charge(macro_atom.getFormalCharge());
        arAtoms[nAtm]=false;

        mAtom_mapping.insert(std::make_pair(atom,&macro_atom));
        positions.insert(std::make_pair(&macro_atom,nAtm));
        if (mToGraph){nAtm++;continue;}

if (mWArom){
        if ((macro_atom.getMOL2() == "C.ar"
                || macro_atom.getMOL2() == "N.ar"
                ||macro_atom.getMOL2() == "N.pl3"
                ) || isAtomInAromaticRing(macro_atom))
        {
//std::cout <<macro_atom.getIdentifier()<<" " <<macro_atom.getMOL2()<<std::endl;
         arAtoms[nAtm]=true;
        }
}


//        cout << macro_atom.getIdentifier()<<"|"<< (unsigned)macro_atom.getFormalCharge()<<" |" <<nAtm<<"| |" << arAtoms[nAtm]<<"|"<<endl;

nAtm++;
    }
}catch(ProtExcept &e)
{
    e.addHierarchy("MacroToIW::convertAtoms");
    throw;
}





void MacroToIW::convertBonds()
try{
size_t nbd=0;
    for(size_t iBd=0;iBd < mMacro.numBonds();++iBd)
    {
        const protspace::MMBond& bd = mMacro.getBond(iBd);
        if (mOnlyUsed &&!bd.isSelected())continue;
        const protspace::MMAtom& at1(bd.getAtom1());
        const protspace::MMAtom& at2(bd.getAtom2());
        if(at1.getAtomicNum()==16 && at2.getAtomicNum()==16)  continue;
        /// Solve issue with 1gws - Bug #15947
        if (at1.getAtomicNum()==16 && at1.getResidue().getName()=="CYS")continue;
        if (at2.getAtomicNum()==16 && at2.getResidue().getName()=="CYS")continue;
        if ((at1.isMetallic() || at2.isMetallic()))continue;
        if(positions.find(&bd.getAtom1())==positions.end()
        ||  positions.find(&bd.getAtom2())==positions.end()) continue;
        if (mToGraph)
        {

            mIWMole.add_bond(positions.at(& bd.getAtom1()),
                             positions.at(& bd.getAtom2()),
                             SINGLE_BOND);
            continue;
        }
        const unsigned short& btype = bd.getType();
        bond_type_t mbtype=UNKNOWN_BOND_TYPE;
if (mWArom){
        if(btype==BOND::AROMATIC_BD||
                btype==BOND::DELOCALIZED)
        {
//                   std::cout <<bd.toString()<<std::endl;
            arBonds[nbd]=1;
        }
        else if (arAtoms[positions.at(&bd.getAtom1())]==1 &&
                 arAtoms[positions.at(&bd.getAtom2())]==1)
        {
            arBonds[nbd]=1;
//            std::cout <<bd.toString()<<std::endl;
        }
        else arBonds[nbd]=0;
}else arBonds[nbd]=0;
//        cout << bd.toString()<<" "<<arBonds[nbd]<<endl;
        switch(btype)
        {
        case BOND::UNDEFINED:

            break;
        case BOND::AMIDE:
        case BOND::SINGLE:
            mbtype=SINGLE_BOND;
            break;
        case BOND::DOUBLE: mbtype=DOUBLE_BOND;
            break;
        case BOND::TRIPLE: mbtype=TRIPLE_BOND;
            break;
        case BOND::AROMATIC_BD: mbtype=SINGLE_BOND ;
            break;
        case BOND::DELOCALIZED:mbtype=SINGLE_BOND;
            break;
        default:

            mbtype=UNKNOWN_BOND_TYPE;
        }++nbd;

//        std::cout << positions.at(&bd.getAtom1())<<" " << positions.at(&bd.getAtom2())<<std::endl;
        mIWMole.add_bond(positions.at(& bd.getAtom1()),
                         positions.at(& bd.getAtom2()),
                         mbtype);
    }
}catch(ProtExcept &e)
{
    assert(e.getId()!="350501"); /// bond must be in boundaries
    e.addHierarchy("MacroToIW::convertBonds");
    throw;
}


void MacroToIW::generateRings(const bool& cleanRing)
try{

    vector<protspace::MMAtom*> atomlist;
    mIWMole.compute_aromaticity();
    const int nrings=  mIWMole.nrings ();
    if (cleanRing)mMacro.clearRing();
    for (int iRing=0; iRing<nrings;++iRing)
    {
        const Ring & ring = *mIWMole.ringi(iRing);

        atomlist.clear();
        for (unsigned int nAtm=0;nAtm < ring.size();++nAtm)
        {
            const  Atom& atm=mIWMole.atom(ring[nAtm]);

            if (mAtom_mapping.find(&atm)==mAtom_mapping.end())
                throw_line("XXXXXX",
                           "MacroToIW::generateRings",
                           "Unable to find match between molecule::Atom and MacroMole::MMAtom");
            protspace::MMAtom& mmatom=*mAtom_mapping.at(&atm);
            atomlist.push_back(&mmatom);
        }
        if (atomlist.at(0)->getResidue().getName() == "ACE")continue;
        short stat[NB_BOND];
        for(size_t i=0;i< NB_BOND;++i)stat[i]=0;
        for(size_t iAtm=0;iAtm< atomlist.size();++iAtm)
        {
            protspace::MMAtom& atom1 = *atomlist.at(iAtm);
            for(size_t jAtm=iAtm+1;jAtm< atomlist.size();++jAtm)
            {
                protspace::MMAtom& atom2 = *atomlist.at(jAtm);
                if (!atom1.hasBondWith(atom2))continue;
                protspace::MMBond& bond = atom1.getBond(atom2);
                switch(bond.getType())
                {
                case BOND::UNDEFINED:stat[0]++;break;
                case BOND::SINGLE:stat[1]++;break;
                case BOND::DOUBLE:stat[2]++;break;
                case BOND::TRIPLE:stat[3]++;break;
                case BOND::DELOCALIZED:stat[4]++;break;
                case BOND::AROMATIC_BD:stat[5]++;break;
                case BOND::AMIDE:stat[6]++;break;
                case BOND::QUADRUPLE:stat[7]++;break;
                case BOND::DUMMY:stat[8]++;break;
                case BOND::FUSED:stat[9]++;break;
                }
            }
        }

    protspace::MMRing& ringm=mMacro.addRingSystem(atomlist, (const bool &) ring.is_aromatic());

    }
}catch(ProtExcept &e)
{

    assert(e.getId()!="350101");
    assert(e.getId()!="352601");
    e.addHierarchy("MAcroToIW::GenerateRings");
    throw;
}





std::string MacroToIW::getUniqueSMILES()
{
    IWString smi=mIWMole.unique_smiles();
    const std::string val(smi.c_str());
    return val;

}






std::string MacroToIW::getSMILES()
{
    IWString smi=mIWMole.smiles();
    const std::string val(smi.c_str());
    return val;
}


int MacroToIW::standardize()
{
    Chemical_Standardisation cs;
    ///TODO: Check if Hydrogen is deleted/created or
    cs.activate_all_except_hydrogen_removal();
    return cs.process(mIWMole);

}

protspace::MMAtom &MacroToIW::getMMAtomFromAtomPos(const size_t &pos)
{
    for(auto it:positions)
        if (it.second == pos)return *it.first;
    throw_line("","","");
}
