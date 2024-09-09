#include <iomanip>
#include <iostream>
#include <map>
#include <ctime>
#include <sstream>
#include <unistd.h>
#include <pwd.h>
#include "headers/parser/molewrite.h"
#include "headers/molecule/macromole.h"
#include "headers/statics/intertypes.h"
#include "headers/molecule/mmresidue_utils.h"
using namespace protspace;

using namespace std;

MoleWrite::MoleWrite():mPath(""),mOnlyUsed(false),mUseRotation(false)
{

}





MoleWrite::MoleWrite(const std::string& mPath)throw(ProtExcept):
    mPath(mPath),mOnlyUsed(false),mUseRotation(false)
{
    try{
        open();
    }catch (ProtExcept &e)
    {

        e.addHierarchy("MoleWrite::MoleWrite");
        e.addDescription("Given mPath : "+ mPath);

        throw;
    }
}






MoleWrite::~MoleWrite(){if (mOfs.is_open()) mOfs.close();}





MoleWrite::MoleWrite(const std::string &mPath,
                     const MacroMole& molecule)throw(ProtExcept):
    mPath(mPath),mOnlyUsed(false),mUseRotation(false)
{

    try{
        open();
        const size_t pos= mPath.find_last_of(".");
        const string extension=mPath.substr(pos);
        if (extension == ".mol2") writeMOL2(molecule);
        else if (extension == ".pdb") writePDB(molecule);
        else throw_line("600201",
                              "MoleWrite::MoleWrite",
                              "Extension not recognized");
    }catch (ProtExcept &e)
    {

        if (e.getId() !="600201") e.addHierarchy("MoleWrite::MoleWrite");
        e.addDescription("Given mPath : "+ mPath);
        e.addDescription("Molecule to save : "+ molecule.getName());
        throw;
    }

}





void MoleWrite::open() throw(ProtExcept)
{
    if (mOfs.is_open()) mOfs.close();
    if (mPath.empty())  throw_line("600101",
                                         "MoleWrite::open",
                                         "No mPath given");

    mOfs.open(mPath.c_str(),std::ios::out);
    if (!mOfs.is_open()) throw_line("600102",
                                          "MoleWrite::open",
                                          "Unable to open file");
}





void MoleWrite::writeMOL2(const MacroMole& molecule)throw(ProtExcept)
{
    if (!mOfs.is_open())
        throw_line("610101",
                         "MoleRead::writeMOL2",
                         "No file opened");


    // STEP 1 : Scan over all chain/residue/atom to see if we consider them
    // in the output file

    vector<const MMChain*> chainToConsider;       bool isChainConsidered=false;
    vector<const MMResidue*> residueToConsider;   bool isResidueConsidered=false;
    vector<const MMAtom* > atomToConsider;
    vector<const MMBond*> bondToConsider;
    for (size_t iCh=0; iCh < molecule.numChains(); ++iCh)
    {
        const MMChain& chain=molecule.getChain(iCh);

        if (mOnlyUsed && !chain.isSelected())continue;

        isChainConsidered=false;

        for (size_t iRes=0;iRes < chain.numResidue(); ++iRes)
        {
            const MMResidue& residue=chain.getResidue(iRes);
            if (mOnlyUsed && !residue.isSelected())continue;

            isResidueConsidered=false;

            for (size_t iAtm=0; iAtm < residue.numAtoms();++iAtm)
            {
                const MMAtom& atom =residue.getAtom(iAtm);

                if (mOnlyUsed && !atom.isSelected())continue;
                atomToConsider.push_back(&atom);
                isResidueConsidered=true;
            }
            if (!isResidueConsidered ) continue;
            residueToConsider.push_back(&residue);
            isChainConsidered=true;
        }
        if (isChainConsidered) chainToConsider.push_back(&chain);
    }
    const MMResidue& tmpRes = molecule.getcTempResidue();
    isResidueConsidered=false;
    for(size_t iAtm=0; iAtm <tmpRes.numAtoms();++iAtm)
    {
        const MMAtom& atom = tmpRes.getAtom(iAtm);
        if (atom.getName().substr(0,2)=="Du")continue;
        if (mOnlyUsed && !atom.isSelected())continue;
        atomToConsider.push_back(&atom);
        isResidueConsidered=true;
    }
    if (isResidueConsidered) residueToConsider.push_back(&tmpRes);

    for (size_t iBd=0; iBd < molecule.numBonds();++iBd)
    {
        const MMBond& bond = molecule.getBond(iBd);
        if (mOnlyUsed && !bond.isSelected()) continue;
        bondToConsider.push_back(&bond);
    }

    // STEP 2 : HEADER PART :
//    time_t now = time(0);   // get time now
//    struct tm  tstruct;
//    char       buf[80];
//    tstruct = *localtime(&now);
    // Visit http://en.cppreference.com/w/cpp/chrono/c/strftime
    // for more information about date/time format
  //  strftime(buf, sizeof(buf), "%Y-%m-%d.%X", &tstruct);
  //  mOfs <<"# Creation date "<< buf<<"\n";

    struct passwd *pws;

    pws = getpwuid(geteuid());
    mOfs <<"# By: "<<pws->pw_name<<"\n";


    /**
     * mol_name
     * num_atoms [num_bonds [num_subst [num_feat [num_sets]]]]
     * mol_type
     * charge_type
     * [status_bits
     * [mol_comment]]
     *      • mol_name (all strings on the line) = the name of the molecule.
     *      • num_atoms (integer) = the number of atoms in the molecule.
     *      • num_bonds (integer) = the number of bonds in the molecule.
     *      • num_subst (integer) = the number of substructures in the molecule.
     *      • num_feat (integer) = the number of features in the molecule.
     *      • num_sets (integer) = the number of sets in the molecule.
     *      • mol_type (string) = the molecule type: SIALL, BIOPOLYIER, PROTEIN, NUCLEIC_ACID, SACCHARIDE
     *      • charge_type (string) = the type of charges associated with the molecule:
     *          NO_CHARGES, DEL_RE, GASTEIGER, GAST_HUCK, HUCKEL,
     *          PULLIAN, GAUSS80_CHARGES, AIPAC_CHARGES,
     *          IULLIKEN_CHARGES, DICT_ CHARGES, IIFF94_CHARGES,
     *          USER_CHARGES
     *      • status_bits (string) = the internal SYBYL status bits associated with the
     *          molecule. These should never be set by the user. Valid status bits are
     *          system, invalid_charges, analyzed, substituted, altered or ref_angle.
     *      • mol_comment (all strings on data line) = the comment associated with
     *          the molecule.

*/
    mOfs << "@<TRIPOS>MOLECULE\n"
         << ((molecule.getName().length()==0)?"unknown":molecule.getName())<<"\n"
         << atomToConsider.size()  << " "
         << bondToConsider.size()  << " "
         << residueToConsider.size() << " "
         << "0 " // Number of features in the molecule
         << "0 " // Number of sets in the molecule
         << "\n";
//    switch (molecule.getMoleType())
//    {
//    case MacroMole::LIGAND:
//    case MacroMole::CAVITY:
//    case MacroMole::UNDEFINED:
//        mOfs << "SMALL\n";break;
//    case MacroMole::PROTEIN:
//        mOfs << "PROTEIN\n";break;


//    }
    mOfs<<"SMALL\n";
    mOfs << "USER_CHARGES\n"
         << "\n" // status_bits
         << "\n";// mol_comment




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


    mOfs << "@<TRIPOS>ATOM\n";
    for (size_t iAtm=0;iAtm < atomToConsider.size();++iAtm)
    {
        const MMAtom& atom = *atomToConsider.at(iAtm);
        mOfs << std::right << setw(7) << (iAtm+1)<<" "
             << std::left  << setw(6) << atom.getName() << " ";

            mOfs << std::right << setw(10) <<std::fixed<<std::setprecision(4)<<atom.pos().x()<<" "
                 << std::right << setw(10) <<std::fixed<<std::setprecision(4)<<atom.pos().y() << " "
                 << std::right << setw(10) <<std::fixed<<std::setprecision(4)<<atom.pos().z();

ostringstream oss;
oss<<atom.getResidue().getName()<<atom.getResidue().getFID();
        mOfs << " "<< std::left<< setw(5)<< atom.getMOL2()<<" "
             << std::right<<setw(5)<<std::distance(residueToConsider.begin(),
                                                   find(residueToConsider.begin(),residueToConsider.end(),&atom.getResidue()))+1
             << " "<< std::left << setw(9)<<oss.str()
                   << " "<< setw(7) <<std::fixed<<std::setprecision(4)<<(double)atom.getFormalCharge()<<"\n";
    }
    if (!bondToConsider.empty())
    {
        mOfs << "@<TRIPOS>BOND\n";
        for (size_t iBond=0;iBond < bondToConsider.size();++iBond)
        {
            const MMBond& bond = *bondToConsider.at(iBond);
            mOfs << std::right << setw(6) << (iBond+1)<<" "

                 << std::left  << setw(7) <<
                    std::distance(atomToConsider.begin(),
                                  find(atomToConsider.begin(),
                                       atomToConsider.end(),
                                       &bond.getAtom1()))+1
                 << " " <<std::left << setw(7)
                 <<std::distance(atomToConsider.begin(),
                                 find(atomToConsider.begin(),
                                      atomToConsider.end(),
                                      &bond.getAtom2()))+1
                << " ";
            switch( bond.getType())
            {
            }
            if (bond.getType() ==  BOND::SINGLE) mOfs << "1\n";
            else if (bond.getType() ==  BOND::DOUBLE) mOfs << "2\n";
            else if (bond.getType() ==  BOND::TRIPLE) mOfs << "3\n";
            else if (bond.getType() ==  BOND::AROMATIC_BD) mOfs << "ar\n";
            else if (bond.getType() ==  BOND::DELOCALIZED) mOfs << "ar\n";
            else if (bond.getType() ==  BOND::DUMMY) mOfs << "du\n";
            else if (bond.getType() ==  BOND::AMIDE) mOfs << "am\n";
            else  mOfs<<"un\n";

        }
    }

    if (!residueToConsider.empty())
    {
        mOfs << "@<TRIPOS>SUBSTRUCTURE\n";
        for (size_t iRes=0;iRes < residueToConsider.size();++iRes)
        {

            try{
            const MMResidue& residue = *residueToConsider.at(iRes);

            mOfs << std::right << setw(6) << (iRes+1)<<" ";
            ostringstream ossx; ossx<<residue.getName()<<residue.getFID();
            mOfs<< std::left <<setw(7)<<ossx.str()<<" ";
            if (residue.getResType()==(RESTYPE::STANDARD_AA)
                    ||residue.getResType()==(RESTYPE::MODIFIED_AA))
            {
                bool found=false;
                for (size_t iAtm=0; iAtm < residue.numAtoms();++iAtm)
                {
                    if (residue.getAtom(iAtm).getName()=="CA")
                    {
                        mOfs  << setw(5) <<  (std::distance(atomToConsider.begin(),
                                                            find(atomToConsider.begin(),
                                                                 atomToConsider.end(),&residue.getAtom(iAtm)))+1)<<" ";
                        found =true;break;
                    }
                }
                if (!found)
                    mOfs  << setw(5) << (std::distance(atomToConsider.begin(),
                                                       find(atomToConsider.begin(),
                                                            atomToConsider.end(),&residue.getAtom(0)))+1) <<" ";
            }
            else
                mOfs   << setw(5) << (std::distance(atomToConsider.begin(),
                                                    find(atomToConsider.begin(),
                                                         atomToConsider.end(),&residue.getAtom(0)))+1) <<" ";
            mOfs<< "RESIDUE 1 "<< residue.getChainName()
                << " "<< setw(3)<<residue.getName();
            vector<MMResidue*> list;
            getLinkedResidue(residue,list);
            mOfs<< " "<<list.size()<<"\n";
}catch(ProtExcept &e)
        {cerr <<"MOLEWRITE : \n"<<e.toString()<<endl;}


        }
        mOfs<<"\n";
    }
    mOfs << "$$$$\n";
}







void MoleWrite::writePDB(const MacroMole &molecule)throw(ProtExcept)
{
    if (!mOfs.is_open())
        throw_line("620101",
                         "MoleRead::writePDB",
                         "No file opened");
    mOfs << std::left << setw(6)<<"HEADER"
         << " "
         << std::left << setw(39)<< molecule.getName()<<"\n";

    ostringstream bonds;
    std::map<const MMAtom*,unsigned int> mapping;

    unsigned int atom_id=1;
    for (size_t iCh=0; iCh < molecule.numChains(); ++iCh)
    {
        const MMChain& chain=molecule.getChain(iCh);

        if (mOnlyUsed && !chain.isSelected())continue;

        for (size_t iRes=0;iRes < chain.numResidue(); ++iRes)
        {
            const MMResidue& residue=chain.getResidue(iRes);
            if (mOnlyUsed && !residue.isSelected())continue;


            for (size_t iAtm=0; iAtm < residue.numAtoms();++iAtm)
            {
                const MMAtom& atom =residue.getAtom(iAtm);

                if (mOnlyUsed && !atom.isSelected())continue;
                mOfs << std::left << setw(6)<< "ATOM"
                     << std::right << setw(5) << atom_id << " "
                     << std::left << setw(4) << atom.getName()
                     << " " // Alternate location editor
                     << std::left << setw(3) << residue.getName() << " "
                     << std::left << setw(1) << residue.getChainName()
                     << std::right << setw(4) << residue.getFID()
                     << " " // Code for insertion of MMResiduees
                     << "   ";

                    mOfs<< std::right << setw(8) <<std::fixed<<std::setprecision(3)<<atom.pos().x()
                        << std::right << setw(8) <<std::fixed<<std::setprecision(3)<<atom.pos().y()
                        << std::right << setw(8) <<std::fixed<<std::setprecision(3)<<atom.pos().z();
               mOfs<< std::left << setw(6) <<std::fixed<<std::setprecision(2)
                    <<"      "
                    << std::left << setw(6) <<std::fixed<<std::setprecision(2)
                   <<atom.getBFactor()
                  << std::right << setw(2) <<atom.getElement();
                switch (atom.getFormalCharge())
                {
                case -2: mOfs <<"2-";break;
                case -1: mOfs <<"1-";break;
                case 1: mOfs <<"1+";break;
                case 2: mOfs <<"2+";break;
                }
                mOfs << "\n";
                mapping.insert(pair<const MMAtom*, unsigned int>(&atom,atom_id));
                atom_id++;


            }
        }
    }
    const MMResidue& tmpRes = molecule.getcTempResidue();
    for(size_t iAtm=0; iAtm <tmpRes.numAtoms();++iAtm)
    {
        const MMAtom& atom = tmpRes.getAtom(iAtm);

        if (mOnlyUsed && !atom.isSelected())continue;

        mOfs << std::left << setw(6)<< "ATOM"
             << std::right << setw(5) << atom_id << " "
             << std::left << setw(4) << atom.getName()
             << " " // Alternate location editor
             << std::left << setw(3) << "TIP "
             << std::left << setw(1) << "X"
             << std::right << setw(4) << tmpRes.getFID()
             << " " // Code for insertion of MMResiduees
             << "   ";

            mOfs<< std::right << setw(8) <<std::fixed<<std::setprecision(3)<<atom.pos().x()
                << std::right << setw(8) <<std::fixed<<std::setprecision(3)<<atom.pos().y()
                << std::right << setw(8) <<std::fixed<<std::setprecision(3)<<atom.pos().z();
         //<< std::left << setw(6) <<std::fixed<<std::setprecision(2)
        mOfs<<"      "
              //<< std::left << setw(6) <<std::fixed<<std::setprecision(2)
           <<"      "
          << std::right << setw(2) <<atom.getElement();
        switch (atom.getFormalCharge())
        {
        case -2: mOfs <<"2-";break;
        case -1: mOfs <<"1-";break;
        case 1: mOfs <<"1+";break;
        case 2: mOfs <<"2+";break;
        }
        mOfs << "\n";
        mapping.insert(pair<const MMAtom*, unsigned int>(&atom,atom_id));
        atom_id++;

    }


    for (size_t iCh=0; iCh < molecule.numChains(); ++iCh)
    {
        const MMChain& chain=molecule.getChain(iCh);
        if (mOnlyUsed && !chain.isSelected())continue;
        for (size_t iRes=0;iRes < chain.numResidue(); ++iRes)
        {
            const MMResidue& residue=chain.getResidue(iRes);
            if (mOnlyUsed && !residue.isSelected())continue;
            for (size_t iAtm=0; iAtm < residue.numAtoms();++iAtm)
            {
                MMAtom& atom = residue.getAtom(iAtm);
                if (mOnlyUsed && !atom.isSelected())continue;
                const unsigned int &pos1=mapping.at(&atom);
                bonds.str("");

                for (size_t iBd=0; iBd < atom.numBonds(); ++iBd)
                {
                    MMAtom& atom2=atom.getAtom(iBd);
                    if (mOnlyUsed && !atom2.isSelected())continue;
                    const unsigned int &pos2=mapping.at(&atom2);
                    if (pos2< pos1)continue;
                    bonds << std::right<<setw(5)<<pos2;
                }
                atom_id++;
                if (bonds.str().empty()) continue;
                mOfs << std::left << setw(6)<<"CONECT"
                     << std::right << setw(5)<<pos1
                     << bonds.str()<<"\n";

            }
        }
    }

    for (size_t iAtm=0; iAtm < tmpRes.numAtoms();++iAtm)
    {
        MMAtom& atom = tmpRes.getAtom(iAtm);
        if (mOnlyUsed && !atom.isSelected())continue;
        const unsigned int &pos1=mapping.at(&atom);
        bonds.str("");

        for (size_t iBd=0; iBd < atom.numBonds(); ++iBd)
        {
            MMAtom& atom2=atom.getAtom(iBd);
            if (mOnlyUsed && !atom2.isSelected())continue;
            const unsigned int &pos2=mapping.at(&atom2);
            if (pos2< pos1)continue;
            bonds << std::right<<setw(5)<<pos2;
        }
        atom_id++;
        if (bonds.str().empty()) continue;
        mOfs << std::left << setw(6)<<"CONECT"
             << std::right << setw(5)<<pos1
             << bonds.str()<<"\n";

    }
}






void MoleWrite::writeMolecule(const MacroMole& molecule) throw(ProtExcept)
{
    const size_t pos = mPath.find_last_of(".");

    if (pos == string::npos)
        throw_line("600301",
                         "MoleWrite::writeMolecule",
                         "No extension found in file mPath : "+mPath);
    const std::string extension=mPath.substr(pos);

    try{
        if (extension == ".pdb") writePDB(molecule);
        else if (extension == ".mol2") writeMOL2(molecule);
        else throw_line("600302",
                              "MoleWrite::writeMolecule",
                              "Unrecognized extension "+extension+
                              " in output file mPath "+mPath);
    } catch(ProtExcept &e)
    {
        if (e.getId() == "600302") throw;
        else
        {
            e.addHierarchy("MoleWrite::writeMolecule");
            e.addDescription("Molecule name : "+molecule.getName()+"\n"
                             +"Output mPath : "+mPath);
            throw;
        }
    }
}







void MoleWrite::newFile(const std::string& pFile) throw(ProtExcept)
{
    try
    {
        if (mOfs.is_open())
            mOfs.close();
        mPath=pFile;
        open();
    }catch(ProtExcept &e)
    {
        e.addHierarchy("MoleWrite::newFile");
        e.addDescription("New file to open : "+pFile);
        throw;
    }
}




void MoleWrite::writeSDF(const MacroMole& pMolecule) throw(ProtExcept)
{
    if (!mOfs.is_open())
        throw_line("630101",
                         "MoleWrite::writeSDF",
                         "No file opened");


    // STEP 1 : Scan over all chain/residue/atom to see if we consider them
    // in the output file

    vector<const MMChain*> chainToConsider;       bool isChainConsidered=false;
    vector<const MMResidue*> residueToConsider;   bool isResidueConsidered=false;
    vector<const MMAtom* > atomToConsider;
    vector<const MMBond*> bondToConsider;
    for (size_t iCh=0; iCh < pMolecule.numChains(); ++iCh)
    {
        const MMChain& chain=pMolecule.getChain(iCh);

        if (mOnlyUsed && !chain.isSelected())continue;

        isChainConsidered=false;

        for (size_t iRes=0;iRes < chain.numResidue(); ++iRes)
        {
            const MMResidue& residue=chain.getResidue(iRes);
            if (mOnlyUsed && !residue.isSelected())continue;

            isResidueConsidered=false;

            for (size_t iAtm=0; iAtm < residue.numAtoms();++iAtm)
            {
                const MMAtom& atom =residue.getAtom(iAtm);

                if (mOnlyUsed && !atom.isSelected())continue;
                atomToConsider.push_back(&atom);
                isResidueConsidered=true;
            }
            if (!isResidueConsidered ) continue;
            residueToConsider.push_back(&residue);
            isChainConsidered=true;
        }
        if (isChainConsidered) chainToConsider.push_back(&chain);
    }
    const MMResidue& tmpRes = pMolecule.getcTempResidue();
    isResidueConsidered=false;
    for(size_t iAtm=0; iAtm <tmpRes.numAtoms();++iAtm)
    {
        const MMAtom& atom = tmpRes.getAtom(iAtm);

        if (mOnlyUsed && !atom.isSelected())continue;
        atomToConsider.push_back(&atom);
        isResidueConsidered=true;
    }
    if (isResidueConsidered) residueToConsider.push_back(&tmpRes);

    for (size_t iBd=0; iBd < pMolecule.numBonds();++iBd)
    {
        const MMBond& bond = pMolecule.getBond(iBd);
        if (mOnlyUsed && !bond.isSelected()) continue;
        bondToConsider.push_back(&bond);
    }

    if (atomToConsider.size() >999)
        throw_line("630102",
                         "MoleWrite::writeSDF",
                         "SDF file limited to 999 atoms");
    if (bondToConsider.size() >999)
        throw_line("630103",
                         "MoleWrite::writeSDF",
                         "SDF file limited to 999 bonds");
    // STEP 2 : HEADER PART :
    time_t now = time(0);   // get time now
    struct tm  tstruct;
    char       buf[80];
    tstruct = *localtime(&now);
    // Visit http://en.cppreference.com/w/cpp/chrono/c/strftime
    // for more information about date/time format
    strftime(buf, sizeof(buf), "%Y-%m-%d.%X", &tstruct);

    mOfs <<"\n fetchPDB\n";
    mOfs  << ((pMolecule.getName().length()==0)?"unknown":pMolecule.getName())<<"\n";
    mOfs<<std::right << setw(3)<< atomToConsider.size()
       <<std::right << setw(3)<< bondToConsider.size()
      <<std::right << setw(3)<<"0"
     <<std::right << setw(3)<<"0"
    <<std::right << setw(3)<<"0"//Chiral
    <<std::right << setw(3)<<"0"//number of stext entries
    <<std::right << setw(12)<<" "//obsolete
    <<std::right << setw(3)<<"999"//Number of lins of additional properties
    <<" V2000\n";

    for(size_t iAtm=0;iAtm < atomToConsider.size();++iAtm)
    {
        const MMAtom& atom=*atomToConsider.at(iAtm);


            mOfs<< std::right << setw(10) <<std::fixed<<std::setprecision(4)<<atom.pos().x()
                << std::right << setw(10) <<std::fixed<<std::setprecision(4)<<atom.pos().y()
                << std::right << setw(10) <<std::fixed<<std::setprecision(4)<<atom.pos().z();

        mOfs<<" ";
        mOfs << std::left<<setw(3)<<atom.getElement()
             << setw(3)<<"0";
        switch(atom.getFormalCharge())
        {
        case  3: mOfs<<setw(3)<<"1";break;
        case  2: mOfs<<setw(3)<<"2";break;
        case  1: mOfs<<setw(3)<<"3";break;
        case  0: mOfs<<setw(3)<<"0";break;
        case -1: mOfs<<setw(3)<<"5";break;
        case -2: mOfs<<setw(3)<<"6";break;
        case -3: mOfs<<setw(3)<<"7";break;
        }
        mOfs<<setw(3)<<"0"//Number of implicit H
           <<setw(3)<<"0"// Stereo care box
          <<setw(3)<<"0"// Valence
         <<setw(3)<<"0";
        for(size_t i=0;i<6;++i)
            mOfs<<setw(3)<<"0";
        mOfs<<"\n";
    }

    for(size_t iBond=0; iBond < bondToConsider.size();++iBond)
    {
        const MMBond& bond = *bondToConsider.at(iBond);
        mOfs  << std::left  << setw(3) <<
                 std::distance(atomToConsider.begin(),
                               find(atomToConsider.begin(),
                                    atomToConsider.end(),
                                    &bond.getAtom1()))+1
              << " " <<std::left << setw(3)
              <<std::distance(atomToConsider.begin(),
                              find(atomToConsider.begin(),
                                   atomToConsider.end(),
                                   &bond.getAtom2()))+1
             << " ";

        if (bond.getType() ==  BOND::SINGLE) mOfs << "1";
        else if (bond.getType() ==  BOND::DOUBLE) mOfs << "2";
        else if (bond.getType() ==  BOND::TRIPLE) mOfs << "3";
        else if (bond.getType() ==  BOND::AROMATIC_BD) mOfs << "4";
        else if (bond.getType() ==  BOND::DELOCALIZED) mOfs << "4";
        else if (bond.getType() ==  BOND::DUMMY) mOfs << "8";
        else if (bond.getType() ==  BOND::AMIDE) mOfs << "1";
        else  mOfs<<"8";
        mOfs <<"  0  0  0  0\n";

    }

    mOfs<<"M END\n";
    mOfs<< "\n$$$$\n";


}





